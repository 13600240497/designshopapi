<?php
namespace App\Services\RTPrice;

use App\Helpers\SiteHelpers;
use GuzzleHttp\Exception\RequestException;
use App\Exceptions\ApiRequestException;
use App\Helpers\ContainerHelpers;
use App\Helpers\AppHelpers;
use App\Helpers\BeanHelpers;
use App\Helpers\S3Helpers;
use App\Services\Site\IBaseDataProvider;

/**
 * 实时价格
 *
 * @author tianhaishen
 */
class RealTimePrice
{
    /** @var string 网站简码 */
    protected $siteCode;

    /** @var string 语言简码 */
    protected $lang;

    /** @var string 国家站编码 */
    protected $pipeline;

    /** @obj object  站点基础数据提供者对象属性 */
    protected $baseDataProvider = '';

    /**
     * 构造函数
     *
     * @param string $siteCode 网站简码
     * @param string $lang 语言简码
     * @param string $pipeline 国家站编码
     */
    public function __construct(string $siteCode, string $lang, string $pipeline)
    {
        $this->siteCode = $siteCode;
        $this->lang = $lang;
        $this->pipeline = $pipeline;
    }

    /**
     * 获取自动刷新组件，异步数据,商品使用实时价格
     *
     * @param int $pageId 活动页面ID
     * @return array
     */
    public function getUiAsyncData($pageId)
    {
        $redisKey = ContainerHelpers::getRedisKey()->web;
        $redis = ContainerHelpers::getPredisResolveCache();
        $cacheKey = $redisKey->getRealTimePriceKey($this->siteCode, $pageId, $this->lang);
        $cacheBody = $redis->get($cacheKey);
       /*if (!empty($cacheBody)) {
            $jsonBody = AppHelpers::uncompress($cacheBody);
            $logFormat = '实时价格页面[%s %s %s]，从Redis缓存[%s]数据： %s';
            ges_track_log(__CLASS__, $logFormat, $this->siteCode, $this->lang, $this->pipeline, $cacheKey, $jsonBody);
            return AppHelpers::jsonDecode($jsonBody);
        }*/

        //获取组件已生成的S3文件数据
        $jsonDataResult = $this->getPageGoodsData($pageId);
        if (empty($jsonDataResult)) {
            return [];
        }

        $website = SiteHelpers::splitSiteCode($this->siteCode);

        try {
            $dataProvider = empty($this->baseDataProvider) ? BeanHelpers::getBaseDataProvider($this->siteCode) :
                $this->baseDataProvider;
            $apiParams = [
                IBaseDataProvider::API_PARAM_PIPELINE => $this->pipeline,
                IBaseDataProvider::API_PARAM_LANG=> $this->lang,
                IBaseDataProvider::API_PARAM_CLIENT => $website[1]
            ];
            //不在国家范围内容的商品需要过滤显示
            if (in_array($website[0], config('filter.skuFilter.website')) &&
                !in_array(COUNTRY, config('filter.skuFilter.allowCountry'))) {
                $goodsSkuList = \App\Helpers\AppHelpers::getArrayByKey($jsonDataResult, 'goodsSku');
                $goodSkuString = implode($goodsSkuList, ',');
                $apiParams['check_rules'] = 'GOODS_VALIDATE_SPIDER';
                $apiParams['goods_sku'] = $goodSkuString;
                static::FilterInvalidSku($jsonDataResult, $dataProvider->filterGoodsSku($apiParams));
            }

            //配置了检测SKU是否库存为0的过滤
            if (in_array($website[0], config('filter.invalidSku.website'))) {
                $goodsSkuList = \App\Helpers\AppHelpers::getArrayByKey($jsonDataResult, 'goodsSku');
                $goodSkuString = implode($goodsSkuList, ',');
                $apiParams['check_rules'] = 'GOODS_VALIDATE_STOCK';
                $apiParams['goods_sku'] = $goodSkuString;
                static::FilterInvalidSku($jsonDataResult, $dataProvider->filterGoodsSku($apiParams));
            }


            //获取站点商品最新价格
            $goodsListNewestPrice = $this->getGoodsNewestPrice($jsonDataResult);
            if (!empty($goodsListNewestPrice)) {
                //更新最新价到组件数组
                $this->updateGoodsPrice($jsonDataResult, $goodsListNewestPrice);
            }

            $cacheBody = AppHelpers::jsonEncode($jsonDataResult);
//            $redis->setex($cacheKey, 60, AppHelpers::compress($cacheBody));
            $logFormat = '实时价格页面[%s %s %s]，设置Redis缓存[%s]数据： %s';
            ges_track_log(__CLASS__, $logFormat, $this->siteCode, $this->lang, $this->pipeline, $cacheKey, $cacheBody);
        } catch (ApiRequestException $e) {
            $format = $e->getMessage()??'实时价格页面[%s %s %s] 调用站点价格接口异常';
            ges_warning_log(__CLASS__, sprintf($format, $this->siteCode, $this->lang, $this->pipeline));
        }

        return $jsonDataResult;
    }

    /**
     * Notes:过滤无效的SKU
     * User: ${USER}
     * DateTime: ${DATE} ${TIME}
     * @param $goodsSkuList
     * @param $invalidSkuList
     */
    protected function FilterInvalidSku(&$uiGoodsSkuList, $invalidSkuList)
    {
        if (empty($invalidSkuList)) return ;
        foreach ($uiGoodsSkuList as &$uiTabsGoodsSku) {
            foreach ($uiTabsGoodsSku as &$uiTabGoodsSku) {
                $goodSku = explode(',' ,$uiTabGoodsSku['goodsSku']);
                //过滤无效商品
                $uiTabGoodsSku['goodsSku'] = implode(array_diff($goodSku, $invalidSkuList), ',');
                foreach ($uiTabGoodsSku['goodsInfo'] as $i => $goods) {
                    if (in_array($goods['goods_sn'], $invalidSkuList)) unset($uiTabGoodsSku['goodsInfo'][$i]);
                }$uiTabGoodsSku['goodsInfo'] = array_merge($uiTabGoodsSku['goodsInfo']);
            }
            unset($uiTabGoodsSku);
        }
        unset($uiTabsGoodsSku);
    }

    /**
     * 更新商品最新价格
     *
     * @param array $pageUiGoodsListRefer
     * @param array $goodsPriceList
     */
    protected function updateGoodsPrice(&$pageUiGoodsListRefer, $goodsPriceList)
    {
        foreach ($pageUiGoodsListRefer as & $uiRuleGoodsListRefer) {
            foreach ($uiRuleGoodsListRefer as & $uiGoodsListRefer) {
                if (!isset($uiGoodsListRefer['goodsInfo']) || !is_array($uiGoodsListRefer['goodsInfo'])) {
                    continue;
                }

                foreach ($uiGoodsListRefer['goodsInfo'] as & $goodsInfoRefer) {
                    $goodsSku = $goodsInfoRefer['goods_sn'];
                    if (isset($goodsPriceList[$goodsSku]) && (float)$goodsPriceList[$goodsSku]['price'] > 0) {
                        $goodsInfoRefer['shop_price'] = $goodsPriceList[$goodsSku]['price'];
                    }
                }
            }
        }
    }

    /**
     * 获取商品最新价格
     *
     * @param array $pageUiGoodsListRefer
     * @return array
     * @throws ApiRequestException
     */
    protected function getGoodsNewestPrice(&$pageUiGoodsListRefer)
    {
        $goodsSkuList = \App\Helpers\AppHelpers::getArrayByKey($pageUiGoodsListRefer, 'goods_sn');

        if (empty($goodsSkuList)) {
            return [];
        }

        $skuString = join(',', array_unique($goodsSkuList));
        $dataProvider = empty($this->baseDataProvider) ? BeanHelpers::getBaseDataProvider($this->siteCode) :
            $this->baseDataProvider;
        $apiParams = [
            IBaseDataProvider::API_PARAM_PIPELINE => $this->pipeline,
            IBaseDataProvider::API_PARAM_LANG => $this->lang,
            IBaseDataProvider::API_PARAM_PROMOTION => 0
        ];

        $result = $dataProvider->getGoodsPrice($skuString, $apiParams);
        return $dataProvider->getTransformer()->transGoodsPrice($result);
    }

    /**
     * 获取自动刷新组件，异步数据
     *
     * @param int $pageId 活动页面ID
     * @return array
     */
    protected function getPageGoodsData($pageId)
    {
        $s3FileManager = BeanHelpers::getS3FileManager($this->siteCode, $this->lang, $this->pipeline);
        // 非原生页面自动刷新组件数据
        $filename = S3Helpers::getUiAutoRefreshJsonFile($pageId);
        $s3JsonUrl = $s3FileManager->getJsonDataFileUrl($filename);

        try {
            $jsonBody = $s3FileManager->getFileBody($s3JsonUrl);
        } catch (RequestException $e) {
            $jsonBody = '';
            $format = '实时价格没有获取到页面[%s %s %s]商品json文件: %s' . "\r\n" . $e->getMessage();
            ges_warning_log(__CLASS__, sprintf($format, $this->siteCode, $this->lang, $this->pipeline, $s3JsonUrl));
        }

        if (empty($jsonBody)) {
            return [];
        }

        return AppHelpers::jsonDecode($jsonBody, true);
    }
}
