<?php
namespace App\Services\NativePage;

use App\Helpers\ShopHelpers;
use App\Helpers\BeanHelpers;
use App\Services\Site\IBaseDataProvider;
use App\Exceptions\ApiRequestException;

/**
 * 不完整的异步数据
 *
 * 备注： 解析组件异步数据时，有些数据需要二次请求以补充数据的完整性，如ES 搜索时商品的价格和库存不是实现的，
 *       需求二次请求接口获取最新价格和库存
 *
 * @author TianHaisen
 */
class AsyncDataSupplement
{
    /** @var string 数据键 - 商品SKU */
    const KEY_GOODS_SKU = 'goodsSKU';

    /** @var string 结果键 - 价格API */
    const RESULT_KEY_PRICE = 'price';

    /** @var PageUiAsyncDataParser 页面解析器 */
    private $pageParser;

    /** @var array 要二次请求的数据信息 */
    private $data = [];

    /** @var array 返回的数据 */
    private $resultInfo = [];

    /** @var bool 商品价格API调用失败，异常是否抛出 */
    private $throwOnGoodsPriceApiFail = false;

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser
     */
    public function __construct(PageUiAsyncDataParser $pageParser)
    {
        $this->pageParser = $pageParser;
    }

    /**
     * 设置商品价格API调用失败，异常是否抛出
     *
     * @param bool $isThrow 是否抛出
     */
    public function setThrowOnGoodsPriceApiFail($isThrow)
    {
        $this->throwOnGoodsPriceApiFail = $isThrow;
    }

    /**
     * 设置商品SKU
     *
     * @param array|string $sku 商品SKU
     */
    public function setGoodsSKU($sku)
    {
        if (empty($sku)) {
            return;
        }

        if (!isset($this->data[self::KEY_GOODS_SKU])){
            $this->data[self::KEY_GOODS_SKU] = [];
        }

        if (is_string($sku)) {
            $this->data[self::KEY_GOODS_SKU][] = $sku;
        } elseif (is_array($sku)) {
            $this->data[self::KEY_GOODS_SKU] = array_merge($this->data[self::KEY_GOODS_SKU], $sku);
        }
    }

    /**
     * 请求获取商品SKU价格信息
     *
     * @throws ApiRequestException
     */
    public function requestGoodsSkuPriceApi()
    {
        if (!isset($this->data[self::KEY_GOODS_SKU])) {
            return;
        }

        $pipelineCode = $this->pageParser->getPageInfo()->getPipeline();
        $lang = $this->pageParser->getPageInfo()->getLang();
        $skuString = join(',', $this->data[self::KEY_GOODS_SKU]);
        try {
            $dataProvider = BeanHelpers::getBaseDataProvider($this->pageParser->getPageInfo()->getSiteCode());
            $apiParams = [
                IBaseDataProvider::API_PARAM_PIPELINE => $pipelineCode,
                IBaseDataProvider::API_PARAM_LANG => $lang
            ];
            $result = $dataProvider->getGoodsPrice($skuString, $apiParams);
            $this->resultInfo[self::KEY_GOODS_SKU][self::RESULT_KEY_PRICE] = $dataProvider->getTransformer()->transGoodsPrice($result);
        } catch (ApiRequestException $e) {
            if ($this->throwOnGoodsPriceApiFail) {
                throw $e;
            } else {
                ges_error_log(__CLASS__, '获取商品SKU价格API调用错误，不更新价格，使用ES价格');
            }
        }
    }

    /**
     * 获取商品价格
     *
     * @param string $goodsSKU 商品SKU
     * @return array
     * - price 店铺价
     * - promotions 营销信息数据
     */
    public function getGoodsPriceInfo($goodsSKU)
    {
        $resultInfo = [
            'price' => 0.0,
            'promotions' => []
        ];

        if (isset($this->resultInfo[self::KEY_GOODS_SKU][self::RESULT_KEY_PRICE][$goodsSKU])) {
            $priceInfo = $this->resultInfo[self::KEY_GOODS_SKU][self::RESULT_KEY_PRICE][$goodsSKU];
            $resultInfo['price'] = ShopHelpers::getGoodsPrice($priceInfo['price']);
            isset($priceInfo['promotions']) && $resultInfo['promotions'] = $priceInfo['promotions'];
        }
        return $resultInfo;
    }
}
