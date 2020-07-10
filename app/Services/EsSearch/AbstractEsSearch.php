<?php
namespace App\Services\EsSearch;

use App\Helpers\SiteHelpers;

/**
 * ES 搜索抽象类
 *
 * @author tianhaishen
 */
abstract class AbstractEsSearch
{

    /** @var string ES参数字段 - 代理 */
    const PARAM_NAME_AGENT = 'agent';

    /** @var string ES参数字段 - 排序 */
    const PARAM_NAME_SORT_BY = 'sort_by';

    /** @var string ES参数字段 - 聚合 */
    const PARAM_NAME_AGGREGATIONS = 'aggregations';

    /** @var string ES参数字段 - 过滤 */
    const PARAM_NAME_FILTERS = 'filters';

    /** @var string ES参数字段 - 站点的原始属性列表，用于解析搜索结果，属性解析 */
    const PARAM_NAME_SITE_ATTRS = 'site_attrs';

    /** @var string API搜索结果字段 - 商品信息 */
    const RESULT_KEY_GOODS = 'data';

    /** @var string API搜索结果字段 - 聚合信息 */
    const RESULT_KEY_AGG = 'aggData';

    /** @var string 跟分类key名称 */
    const ROOT_CATEGORY_KEY = 'catIdLevel1';

    /** @var array 业务排序规则 */
    const BUSINESS_SORT_REGULAR = [
        SearchParamBuilder::SORT_BY_HOT => [
            ['field' => 'stockFlag', 'order' => 'desc'],
            ['field' => 'week2SalesVolume', 'order' => 'desc'],
            ['field' => 'addTime', 'order' => 'desc']
        ],
        SearchParamBuilder::SORT_BY_TRENDING => [
            ['field' => 'stockFlag', 'order' => 'desc'],
            ['field' => 'dailyRate', 'order' => 'desc'],
            ['field' => 'addTime', 'order' => 'desc']
        ],
        SearchParamBuilder::SORT_BY_NEW => [
            ['field' => 'stockFlag', 'order' => 'desc'],
            ['field' => 'channelGoodsSort', 'order' => 'asc'],
            ['field' => 'newGoodsWeight', 'order' => 'desc'],
            ['field' => 'addTime', 'order' => 'desc']
        ],
        SearchParamBuilder::SORT_BY_PRICE_LOW_TO_HIGH => [
            ['field' => 'stockFlag', 'order' => 'desc'],
            ['field' => 'displayPrice', 'order' => 'asc'],
            ['field' => 'sortOrder', 'order' => 'asc'],
            ['field' => 'addTime', 'order' => 'desc']
        ],
        SearchParamBuilder::SORT_BY_PRICE_HIGH_TO_LOW => [
            ['field' => 'stockFlag', 'order' => 'desc'],
            ['field' => 'displayPrice', 'order' => 'desc'],
            ['field' => 'sortOrder', 'order' => 'asc'],
            ['field' => 'addTime', 'order' => 'desc']
        ]
    ];

    /** @var array ES配置 */
    protected $config;

    /** @var string 站点编码，如: zf-wap */
    protected $siteCode;

    /** @var string 语言简码，如: en/fr */
    protected $lang;

    /** @var string 网站简码, 如: zf */
    protected $websiteCode;

    /** @var string 平台简码, 如: wap */
    protected $platformCode;

    /** @var array 搜索参数 */
    protected $apiParams = [];

    /** @var AbstractTransformer 搜索结果转换器 */
    protected $transformer;

    /**
     * 构造函数
     *
     * @param string $siteCode 站点编码
     * @param string $lang 语言简码
     */
    public function __construct($siteCode, $lang)
    {
        $this->siteCode = $siteCode;
        $this->lang = $lang;
        list($this->websiteCode, $this->platformCode) = SiteHelpers::splitSiteCode($siteCode);
        $this->config = config('es.'. $this->websiteCode);
    }

    /**
     * 根据传入参数构建ES请求参数
     *
     * @param array $params 传入参数
     */
    public abstract function buildRequestParams($params);

    /**
     * 获取API请求参数
     *
     * @return array
     */
    public function getApiParams()
    {
        return $this->apiParams;
    }

    /**
     * 获取结果转换器
     *
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * 获取排序筛选显示列表
     *
     * @return array
     */
    public function getSortByList()
    {
        $sorts = [];
        foreach ($this->config['supportSort'] as $sortCode) {
            $sorts[] = [
                'item_id' => $sortCode,
                'item_title' => __('common.es_sort_'. $sortCode),
            ];
        }
        return $sorts;
    }

    /**
     * 获取支持排序名称列表
     *
     * @return array
     */
    protected function getSupportSorts()
    {
        return $this->config['supportSort'];
    }

    /**
     * 获取ES搜索 API URL地址
     * @return string
     */
    public function getApiUrl()
    {
        return $this->config['url'];
    }

    /**
     * 转换排序规则在 推荐(recommend) 下的BTS分流信息
     *
     * @param array $resultRefer ES 搜索结果
     * @return array|null
     */
    public function transformBtsInfo(&$resultRefer)
    {
        if (array_key_exists('planId', $resultRefer)
            && array_key_exists('bucketId', $resultRefer)
            && array_key_exists('versionId', $resultRefer)
            && array_key_exists('planCode', $resultRefer)
            && array_key_exists('policy', $resultRefer)
            && !empty($resultRefer['planId'])
            && !empty($resultRefer['versionId'])
            && !empty($resultRefer['planCode'])
            && !empty($resultRefer['policy'])
        ) {
            return [
                'planid' => $resultRefer['planId'],
                'bucketid' => $resultRefer['bucketId'],
                'versionid' => $resultRefer['versionId'],
                'plancode' => $resultRefer['planCode'],
                'policy' => $resultRefer['policy'],
            ];
        }
        return null;
    }

    /**
     * 转换属性信息
     *
     * @param array $esParams ES 参数
     * @param array $resultRefer ES 搜索结果
     * @return array
     */
    public function transformAttrInfo($esParams, &$resultRefer)
    {
        return $this->getTransformer()->attrInfo($esParams, $resultRefer);
    }

    /**
     * 是否有商品数据返回
     *
     * @param array $resultRefer ES 搜索结果
     * @return bool
     */
    public function hasGoodsInfo(&$resultRefer) {
        if (isset($resultRefer[self::RESULT_KEY_GOODS]) && !empty($resultRefer[self::RESULT_KEY_GOODS])) {
            return true;
        }
        return false;
    }

    /**
     * 转换商品信息
     *
     * @param array $resultRefer ES 搜索结果
     * @return array
     */
    public function transformGoodsInfo(&$resultRefer)
    {
        if ($this->hasGoodsInfo($resultRefer)) {
            return $this->getTransformer()->goodsInfo($resultRefer[self::RESULT_KEY_GOODS]);
        }
        return [];
    }

    /**
     * 转换分类信息
     *
     * @param array $resultRefer ES 搜索结果
     * @return array
     */
    public function transformCategoryInfo(&$resultRefer)
    {

        if (isset($resultRefer[self::RESULT_KEY_AGG][self::ROOT_CATEGORY_KEY])
            && !empty($resultRefer[self::RESULT_KEY_AGG][self::ROOT_CATEGORY_KEY])
        ) {
            return $this->getTransformer()->categoryInfo($resultRefer[self::RESULT_KEY_AGG][self::ROOT_CATEGORY_KEY]);
        }
        return [];
    }

    /**
     * 获取搜索结果分页信息
     *
     * @param array $resultRefer ES 搜索结果
     * @return array
     */
    public function transformPaginationInfo(&$resultRefer)
    {
        return $this->getTransformer()->getPaginationInfo($resultRefer);
    }


    /**
     * 构建公共参数
     *
     * @param array $params 搜索参数
     */
    protected function buildCommandParams($params)
    {
        $this->apiParams['accessToken'] = $this->config['accessToken'];
        $this->apiParams['version'] = 5;
        $this->apiParams['cache'] = false;
        // 支持 web(pc端)/wap(手机浏览器端)/ios(苹果手机端)/android(安卓手机端)/pad(平板电脑端)/app(移动端)
        $this->apiParams['agent'] = $params[self::PARAM_NAME_AGENT] ?? 'web';
        $this->apiParams['pageSize'] = $params['page_size'] ?? 20;
        $this->apiParams['pageNo'] = $params['page_num'] ?? 1;
    }

    /**
     * 获取商品三级分类聚合
     *
     * @return array
     */
    protected function getCategoryAggregation()
    {
        return [
            'key' => self::ROOT_CATEGORY_KEY,
            'field' => 'categories.catId',
            'size' => 10,
            'filters' => [
                [
                    'field' => 'categories.level',
                    'values' => [1]
                ]
            ],
            'subAggregation' => [
                'key' => 'catIdLevel2',
                'field' => 'categories.catId',
                'size' => 30,
                'filters' => [
                    [
                        'field' => 'categories.level',
                        'values' => [2]
                    ]
                ],
                'subAggregation' => [
                    'key' => 'catIdLevel3',
                    'field' => 'categories.catId',
                    'size' => 30,
                    'filters' => [
                        [
                            'field' => 'categories.level',
                            'values' => [3]
                        ]
                    ],
                    'stat' => 'count'
                ],
                'stat' => 'count'
            ],
            'stat' => 'count'
        ];
    }

    /**
     * 过滤分类
     *
     * @param array $ids 分类ID列表
     * @return array
     */
    protected function getCategoryFilter($ids)
    {
        $targets = $this->getAggTarget();
        return [
            [
                'field' => 'categories.catId',
                'values' => $ids,
                'operator' =>
                'should',
                'pair' => 'catIds',
                'aggsTarget' => $targets
            ],
            [
                'field' => 'extCategories.catId',
                'values' => $ids,
                'operator' => 'should',
                'pair' => 'catIds',
                'aggsTarget' => $targets
            ],
        ];
    }

    protected function getAggTarget()
    {
        $aggList = [];
        foreach ($this->apiParams[self::PARAM_NAME_AGGREGATIONS] as $aggInfo) {
            if (isset($aggInfo['key']) && $aggInfo['key'] != self::ROOT_CATEGORY_KEY) {
                $aggList[] = $aggInfo['key'];
            }
        }
        return $aggList;
    }
}
