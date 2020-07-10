<?php
/**
 * API配置 通过 AppHelpers::getAsyncApiConfig 函数加载配置
 *
 * 配置格式说明
 * - url            接口请求URL
 * - method         请求方式
 * - description    接口描述
 * - support        支持站点
 * - bodyType       POST参数请求格式，默认 form-data-urlencoded
 *
 * @see \App\Helpers\AppHelpers::getAsyncApiConfig
 */

return [
    'verify' => [
        'url' => '/geshop/common/verify',
        'method' => 'post',
        'description' => '统一校验接口',
        'support' => ['zf-pc', 'zf-wap', 'zf-app' ,'dl-web', 'dl-app']
    ],
    'goods_getDetail' => [
        'url' => '/geshop/goods/getdetail',
        'method' => 'post',
        'description' => '商品详情',
        'support' => ['zf-pc', 'zf-wap', 'zf-app' ,'dl-web', 'dl-app']
    ],
    'goods_getTskGoodsDetailByPriceSysIds' => [
        'url' => '/geshop/goods/getTskGoodsDetailByPriceSysIds',
        'method' => 'post',
        'description' => '商品秒杀接口(根据商品价格体系系统队列ID)',
        'support' => ['zf-pc', 'zf-wap', 'zf-app']
    ],
    'sync_baseData' => [
        'url' => env('ZF_PC_API_PREFIX') .'/syn/get_zaful_data.php',
        'method' => 'get',
        'description' => 'zaful站点基础数据(网站商品分类及分类配置的属性模板)获取接口',
        'support' => ['zf-wap', 'zf-app']
    ],
    'fun_discountPrice' => [
        'url' => '/fun/index.php?act=discountPrice',
        'method' => 'post',
        'description' => 'zaful站点价格接口',
        'support' => ['zf-pc', 'zf-wap', 'zf-app' ,'dl-web', 'dl-app']
    ]
];
