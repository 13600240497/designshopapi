<?php
namespace App\Base;

/**
 * 外部系统API相关常量定义
 * Api配置文件: config\asyncapi.php
 *
 * @author TianHaisen
 */
interface ApiConstants
{
    /** @var string ES 搜索API名称 */
    const API_NAME_ES_SEARCH = 'es_search';

    /** @var string API名称 - 获取商品详情接口(对应asyncap配置文件里面的组件下标名称) */
    const API_NAME_GET_DETAIL = 'goods_getDetail';

    /** @var string API名称 - 商品秒杀接口(根据商品价格体系系统队列ID) */
    const API_NAME_GET_TSK_GOODS_DETAIL_BY_PRICE_SYS_IDS = 'goods_getTskGoodsDetailByPriceSysIds';
}
