<?php
namespace App\Services\Site;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use App\Exceptions\ApiRequestException;

/**
 * 站点基础数据接口
 *
 * @author tianhaishen
 */
interface IBaseDataProvider
{
    /** @var string API参数 - 国家站简码，如: ZF/ZFFR */
    const API_PARAM_PIPELINE = 'pipeline';

    /** @var string API参数 - 国家站语言，如: en/es */
    const API_PARAM_LANG = 'lang';

    /** @var string API参数 - 客户端标识，如: pc/wap */
    const API_PARAM_CLIENT = 'client';

    /** @var string API参数 - 是否获取营销信息,获取营销信息会影响性能，如: 0/1 */
    const API_PARAM_PROMOTION = 'promotion';

    /**
     * 获取数据转换器
     *
     * @return IBaseDataTransformer
     */
    public function getTransformer();

    /**
     * 获取指定语言所有商品分类
     *
     * @param string $lang 语言简码，如: en/fr
     * @return array
     * @throws ApiRequestException
     */
    public function getAllCategory($lang);

    /**
     * 过滤指定国家中显示的SKU
     *
     * @param array      接口请求参数[goodsSn,rule,client,pipeline,lang]
     * @return  string 符合过滤条件的SKU字符串
     * @throws ApiRequestException
     */
    public function filterGoodsSku($params);

    /**
     * 获取分类属性列表
     *
     * @param string $lang 语言简码，如: en/fr
     * @param array $params 其他参数
     * @return array
     * @throws ApiRequestException
     */
    public function getCategoryAttributes($lang, $params);

    /**
     * 获取SKU价格
     *
     * @param string $goodsSkuString 商品SKU列表，分个用英文逗号分隔
     * @param array $params 其他参数
     * @return array
     * @throws ApiRequestException
     */
    public function getGoodsPrice($goodsSkuString, $params);

    /**
     * 获取SKU价格,异步调用方式
     *
     * @param string $goodsSkuString 商品SKU列表，分个用英文逗号分隔
     * @param array $params 其他参数
     * @param Client $client 链接对象
     * @return PromiseInterface
     * @throws ApiRequestException
     */
    public function getGoodsPricePromise($goodsSkuString, $params, Client $client);
}
