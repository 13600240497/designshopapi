<?php
namespace App\Services\NativePage\UiTplParser;

use App\Base\KeyConstants;
use App\Base\AppConstants;
use App\Base\GoodsConstants;
use App\Helpers\ShopHelpers;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 组件数据解析器
 *
 * @author TianHaisen
 */
abstract class AbstractUiTplAsyncApiParser
{
    /** @var string HTTP参数名称 - 分页当前页码 */
    const HTTP_PARAM_PAGE_NO = 'page_no';

    /** @var string HTTP参数名称 - ES搜索 - 用户唯一标识，推荐算法统计使用(对应大数据od) */
    const HTTP_PARAM_COOKIE_ID = 'cookie_id';

    /** @var string HTTP参数名称 - ES搜索 - 分流id, 用于AB测试 */
    const HTTP_PARAM_BTS_UNIQUE_ID = 'bts_unique_id';

    /** @var string HTTP参数名称 - ES搜索 - 访问用户国家简码 */
    const HTTP_PARAM_COUNTRY_CODE = 'country_code';

    /** @var string HTTP参数名称 - ES搜索 - 来源平台 */
    const HTTP_PARAM_AGENT = 'agent';


    /** @var string 组件异步数据字段 - 商品信息  */
    const UI_ASYNC_SKU_INFO = 'skuInfo';

    /** @var PageUiAsyncDataParser 页面解析器 */
    protected $pageParser;

    /** @var SkuDataParser 组件商品信息解析器 */
    protected $skuDataParser = null;

    /** @var int 组件ID */
    protected $componentId;

    /** @var bool 是否显示商品营销信息 */
    protected $isShowPromotion = false;

    /** @var bool 是否转换站点 getDetail 接口返回的商品数据为API层标准格式，因为之前商品列表组件还要保持原样返回 */
    protected $isTransStandardGetDetailApiResult = true;

    /**
     * 初始化
     *
     * @param PageUiAsyncDataParser $pageParser
     * @param int $componentId 组件ID
     */
    public function init(PageUiAsyncDataParser $pageParser, $componentId)
    {
        $this->pageParser = $pageParser;
        $this->componentId = $componentId;
    }

    /**
     * getDetail 接口返回的商品数据是否API层标准格式
     *
     * @return bool
     * @see \App\Services\Site\AbstractApiResultTransformer::getGoodsGetDetailGoodsInfo
     */
    public function getTransStandardGetDetailApiResult()
    {
        return $this->isTransStandardGetDetailApiResult;
    }


    /**
     * 获取页面解析器
     *
     * @return PageUiAsyncDataParser
     */
    public function getPageParser()
    {
        return $this->pageParser;
    }

    /**
     * 获取组件ID
     *
     * @return int
     */
    public function getComponentId()
    {
        return $this->componentId;
    }

    /**
     * 是否V2 API版本(V2 是APP端API接口组件和异步数据分离后的新版本)
     *
     * @return bool
     */
    public function isVersionV2()
    {
        return $this->pageParser->getApiVersion() === AppConstants::API_VERSION_V2;
    }

    /**
     * 获取组件HTTP传入参数
     *
     * @param string $key 参数键名
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    public function getHttpParam($key, $defaultValue = null)
    {
        return $this->pageParser->getHttpParam($key, $defaultValue);
    }

    /**
     * 获取页面信息对象
     *
     * @return NativePageInfo
     */
    protected function getPageInfo()
    {
        return $this->pageParser->getPageInfo();
    }

    /**
     * 获取组件SKU配置数据
     *
     * @return array|null
     */
    protected function getSkuData()
    {
        return $this->getPageInfo()->getComponentSkuData($this->componentId);
    }

    /**
     * 是否包括sku_data字段数据
     *
     * @return bool
     */
    protected function hasSkuData()
    {
        return $this->getPageInfo()->hasSkuData($this->componentId);
    }

    /**
     * 获取组件指定键名的设置数据
     *
     * @param string $settingKey 属性键名称
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    protected function getSettingValue($settingKey, $defaultValue = null)
    {
        return $this->getPageInfo()->getComponentSettingValue($this->componentId, $settingKey, $defaultValue);
    }


    /**
     * 根据SKU数据字段生成 API 请求信息, 在这里生成ES搜索参数，
     * Es查询时会根据这里的参数构建查询,参数详细信息查看 AbstractEsSearch::buildRequestParams
     *
     * @param array $skuInfoList 组件SKU配置列表
     * @return array
     * @see \App\Services\EsSearch\ZafulEsSearch::buildRequestParams()
     */
    protected function buildAsyncApiInfoBySkuData($skuInfoList)
    {
        $this->skuDataParser = new SkuDataParser($this->pageParser, $this, $skuInfoList);
        return $this->skuDataParser->buildAsyncApiInfo();
    }

    /**
     * 转换根据SKU数据字段API请求结果
     *
     * @param int $toComponentId 将商品信息设置到目录组件,默认是当前组件
     */
    protected function transformResultBySkuData($toComponentId = 0)
    {
        $skuAsyncInfo = ($this->skuDataParser === null) ? [] : $this->skuDataParser->transformResult();
        if (empty($skuAsyncInfo)) {
            return;
        }

        $uiAsyncInfo = [
            self::UI_ASYNC_SKU_INFO => $skuAsyncInfo
        ];

        if ($toComponentId === 0 || !$this->getPageInfo()->hasComponent($toComponentId)) {
            $toComponentId = $this->componentId;
        }
        $this->pageParser->setComponentAsyncData($toComponentId, $uiAsyncInfo);
    }

    /**
     * 转换根据SKU数据字段ES搜索结果SKU价格
     */
    protected function supplementaryAsyncDataBySkuData()
    {
        $asyncDataListRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        if (isset($asyncDataListRefer[self::UI_ASYNC_SKU_INFO])) {
            $asyncDataSupplement = $this->pageParser->getAsyncDataSupplement();
            foreach ($asyncDataListRefer[self::UI_ASYNC_SKU_INFO] as & $skuItemRefer) {
                $type = (int)$skuItemRefer[SkuDataParser::KEY_SKU_RULE_TYPE];
                if ((AppConstants::SKU_FROM_SOP === $type)
                    && !empty($skuItemRefer[KeyConstants::GOODS_LIST])
                    && is_array($skuItemRefer[KeyConstants::GOODS_LIST])
                ) { // 商品运营平台
                    foreach ($skuItemRefer[KeyConstants::GOODS_LIST] as & $goodsInfoRefer) {
                        $goodsSku = $goodsInfoRefer[GoodsConstants::GOODS_SN];
                        $priceInfo = $asyncDataSupplement->getGoodsPriceInfo($goodsSku);

                        // 营销信息
                        if ($this->isShowPromotion) {
                            $goodsInfoRefer[GoodsConstants::PROMOTIONS] = $priceInfo['promotions'];
                        }

                        // 实时价格
                        if ($priceInfo['price'] > 0) {
                            $goodsInfoRefer[GoodsConstants::SHOP_PRICE] = ShopHelpers::getGoodsPriceString($priceInfo['price']);
                        }
                    }
                    unset($goodsInfoRefer);
                }
            }
        }

        unset($asyncDataListRefer);
    }

    /**
     * 组件关联处理
     */
    public function associationProcessing()
    {
        // 子类覆盖处理
    }

    /**
     * 解析组件数据，生成API请求信息列表
     *
     * @return array
     */
    public abstract function buildApiRequestInfo();

    /**
     * 转换API请求返回
     */
    public abstract function transformApiResult();

    /**
     * 补充完整异步数据
     */
    public abstract function supplementaryAsyncData();

    /**
     * 转换Web格式数据
     */
    public abstract function transformWebData();

    /**
     * 转换APP格式数据
     */
    public abstract function transformAppData();
}
