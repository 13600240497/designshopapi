<?php
namespace App\Services\Site;

/**
 * 服装站点API接口返回转换器抽象
 *
 * @author tianhaishen
 */
abstract class AbstractSiteApiResultTransformer extends AbstractApiResultTransformer
{
    /**
     * 商品秒杀接口(根据商品价格体系ID)
     *
     * @param array $apiResultRefer 接口返回数据引用
     * @return array
     * - goods_list 商品列表
     * - tsk_info 秒杀信息
     * @see \App\Base\ApiConstants::API_NAME_GET_TSK_GOODS_DETAIL_BY_PRICE_SYS_IDS
     */
    public abstract function transGetTskGoodsDetailByPriceSysIds(&$apiResultRefer);
}
