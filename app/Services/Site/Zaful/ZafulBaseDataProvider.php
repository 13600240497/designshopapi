<?php
namespace App\Services\Site\Zaful;

use GuzzleHttp\Client;
use App\Base\AppConstants;
use App\Exceptions\ApiRequestException;
use App\Helpers\AppHelpers;
use App\Helpers\ContainerHelpers;
use App\Services\Site\IBaseDataProvider;
use App\Services\Site\AbstractBaseApiRequest;

/**
 * zaful 站点基础数据
 *
 * @author tianhaishen
 */
class ZafulBaseDataProvider extends AbstractBaseApiRequest implements IBaseDataProvider
{
    /** @var string 密匙参数签名使用 */
    const SECRET_KEY = 'sdfgsdfg_3453adf';

    /** @var string ZF站点基础数据API接口配置名称 */
    const API_NAME_BASE_DATA = 'sync_baseData';

    /** @var string 价格查询接口名称 */
    const API_NAME_BASE_PRICE = 'fun_discountPrice';

    /** @var string 统一检验接口 */
    const API_NAME_BASE_VERIFY = 'verify';

    /** @var ZafulBaseDataTransformer */
    private $transformer = null;

    /**
     * @inheritdoc
     */
    public function getTransformer()
    {
        if ($this->transformer === null) {
            $this->transformer = new ZafulBaseDataTransformer();
        }
        return $this->transformer;
    }

    /**
     * @inheritdoc
     */
    public function getAllCategory($lang)
    {
        $cacheKey = ContainerHelpers::getRedisKey()->api->getSiteCategory(AppConstants::WEBSITE_CODE_ZF, $lang);
        return AppHelpers::getArrayCacheIfPresent($cacheKey, function() use ($lang) {
            return $this->callGetAllCategoryApi($lang);
        }, true, AppConstants::TIME_UNIT_DAY * 7);
    }

    /**
     * 调用分类接口
     *
     * @param string $lang 语言简码
     * @return array
     * @throws ApiRequestException
     */
    private function callGetAllCategoryApi($lang)
    {
        $apiParams = [
            'action' => 'get_all_category',
            'lang' => $lang,
            'token' => md5(self::SECRET_KEY . $lang)
        ];
        $result = $this->callApi($apiParams);
        if (!array_key_exists('status', $result) || ($result['status'] !== 'ok')) {
            throw new ApiRequestException($result['msg'] ?? '请求分类接口错误');
        }

        $apiResult = array_column($result['list'], null, 'cat_id');
        $logFormat = '没有获取到缓存数据, 请求站点分类接口[%s]成功返回: %s';
        ges_track_log(__CLASS__, $logFormat, $apiParams, $apiResult);
        return $apiResult;
    }

    /**
     * @inheritdoc
     */
    public function getCategoryAttributes($lang, $params)
    {
        $templateId = $params['templateId']; // 分类属性模板ID
        $cacheKey = ContainerHelpers::getRedisKey()->api->getSiteAttr(AppConstants::WEBSITE_CODE_ZF, $lang, $templateId);
        return AppHelpers::getArrayCacheIfPresent($cacheKey, function() use ($lang, $templateId) {
            return $this->callGetTemplateInfoApi($lang, $templateId);
        }, true, AppConstants::TIME_UNIT_DAY * 7);
    }

    /**
     * 调用分类属性接口
     *
     * @param string $lang 语言简码
     * @param int $templateId 属性模板ID
     * @return array
     * @throws ApiRequestException
     */
    private function callGetTemplateInfoApi($lang, $templateId)
    {
        $apiParams = [
            'action' => 'get_template_info',
            'template_id' => $templateId,
            'lang' => $lang,
            'token' => md5(self::SECRET_KEY . $lang . $templateId)
        ];
        $result = $this->callApi($apiParams);
        if (!array_key_exists('status', $result) || ($result['status'] !== 'ok')) {
            throw new ApiRequestException($result['msg'] ?? '请求分类属性接口错误');
        }

        $logFormat = '没有获取到缓存数据, 请求站点属性模板接口[%s]成功返回: %s';
        ges_error_log(__CLASS__, $logFormat, $apiParams, $result['attr_list']);
        return $result['attr_list'];
    }

    /**
     * @inheritdoc
     */
    public function getGoodsPrice($goodsSkuString, $params)
    {
        $apiParams = $this->getGoodsPriceApiParams($goodsSkuString, $params);
        $jsonBody = $this->requestApi(self::API_NAME_BASE_PRICE, $apiParams);
        $result = AppHelpers::jsonDecode($jsonBody, true);
        !is_array($result) && $result = [];
        if (!array_key_exists('status', $result) || ((int)$result['status'] !== 0)) {
            throw new ApiRequestException($result['msg'] ?? '请求商品价格接口错误');
        }
        return $result['priceList'];
    }

    /**
     * @inheritDoc
     */
    public function getGoodsPricePromise($goodsSkuString, $params, Client $client)
    {
        $apiParams = $this->getGoodsPriceApiParams($goodsSkuString, $params);
        return $this->asyncRequestApi(self::API_NAME_BASE_PRICE, $apiParams, $client);
    }

    /**
     * @param string $goodsSkuString
     * @param array $params
     * @return array
     */
    private function getGoodsPriceApiParams($goodsSkuString, $params)
    {
        $pipelineCode = $params[static::API_PARAM_PIPELINE] ?? 'ZF'; // 国家站点简码
        $lang = $params[static::API_PARAM_LANG] ?? 'en'; // 语言简码
        $shoPromotion = $params[static::API_PARAM_PROMOTION] ?? 1; // 是否获取商品营销信息
        return [
            static::API_PARAM_PIPELINE => strtolower($pipelineCode),
            static::API_PARAM_LANG => $lang,
            'skus' => $goodsSkuString,
            'isShowDetail' => 0,
            'show_promotion' => $shoPromotion
        ];
    }

    /**
     * Notes:过滤特定条件的SKU(站点统一校校验验过滤商品SKU)
     * User: zhuguoqiang
     * DateTime: 2020-05-04
     * @param array      接口请求参数[goodsSn,rule,client,pipeline,lang]
     * @return  string 符合过滤条件的SKU字符串
     *
     */
    public function filterGoodsSku($params)
    {
        $params['check_type'] = 'goods';//针对商品的过滤校验
        $jsonBody = $this->requestApi(self::API_NAME_BASE_VERIFY, $params);
        $result = AppHelpers::jsonDecode($jsonBody, true);
        !is_array($result) && $result = [];
        if (!array_key_exists('code', $result)) {
            throw new ApiRequestException($result['msg'] ?? '请求过滤校验商品接口错误');
        }
        return $result['data']['invalid_data'];
    }

    /**
     * 请求基础数据API接口
     *
     * @param array $apiParams API请求参数
     * @return array
     * @throws ApiRequestException
     */
    protected function callApi($apiParams)
    {
        $jsonBody = $this->requestApi(static::API_NAME_BASE_DATA, $apiParams);
        $result = AppHelpers::jsonDecode($jsonBody, true);
        !is_array($result) && $result = [];
        return $result;
    }
}
