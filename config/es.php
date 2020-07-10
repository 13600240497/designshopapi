<?php
/**
 * ES搜索配置
 */

return [
    'zf' => [
        'accessToken' => 'dc698883cbca47bbac191787969f9de4',
        'url' => env('ES_API_PREFIX') .'/ZF/search',
        'supportSort' => [ // ES支持排序规则列表
            'recommend',
            'hot',
            'new',
            'price-low-to-high',
            'price-high-to-low'
        ],
        'defaultAttr' => [
            ['title' => 'Color', 'name' => 'Color', 'type' => 2],
            ['title' => 'Size', 'name' => 'Size', 'type' => 1],
        ]
    ],
];
