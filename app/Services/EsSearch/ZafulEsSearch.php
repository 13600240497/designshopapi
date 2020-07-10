<?php
namespace App\Services\EsSearch;

use App\Helpers\AppHelpers;
use App\Services\NativePage\NativePageInfo;

/**
 * zaful 站点 ES搜索
 *
 * @see http://wiki.hqygou.com:8090/pages/viewpage.action?pageId=180356698
 * @see http://wiki.hqygou.com:8090/display/AIGroup/Search+API+Doc+V3.0
 * @author TianHaisen
 */
class ZafulEsSearch extends AbstractEsSearch
{
    /** @var string 国家站点编码，如: ZF */
    private $pipeline;

    /** @var array ES支持索引名称 */
    private static $supportDomain = [
        'ZF' => ['ZF_en'],
        'ZFCH' => ['ZFCH_de', 'ZFCH_fr'],
        'ZFCA' => ['ZFCA_en', 'ZFCA_fr'],
        'ZFAR' => ['ZFAR_ar'],
        'ZFDE' => ['ZFDE_de'],
        'ZFES' => ['ZFES_es'],
        'ZFFR' => ['ZFFR_fr'],
        'ZFIT' => ['ZFIT_it'],
        'ZFPT' => ['ZFPT_pt'],
        'ZFIE' => ['ZFIE_en'],
        'ZFGB' => ['ZFGB_en'],
        'ZFAU' => ['ZFAU_en'],
        'ZFNZ' => ['ZFNZ_en'],
        'ZFBE' => ['ZFBE_fr'],
        'ZFPH' => ['ZFPH_en'],
        'ZFSG' => ['ZFSG_en'],
        'ZFMY' => ['ZFMY_en'],
        'ZFIN' => ['ZFIN_en'],
        'ZFZA' => ['ZFZA_en'],
        'ZFAT' => ['ZFAT_de'],
        'ZFMX' => ['ZFMX_es'],
        'ZFBR' => ['ZFBR_pt'],
        'ZFTH' => ['ZFTH_th'],
        'ZFTW' => ['ZFTW_zh-tw'],
        'ZFID' => ['ZFID_id'],
        'ZFIL' => ['ZFIL_he','ZFIL_en'],
        'ZFTR' => ['ZFTR_tr'],
        'ZFRU' => ['ZFRU_ru'],
        'ZFVN' => ['ZFVN_vi'],
        'ZFHK' => ['ZFHK_zh-tw'],
        'ZFJP' => ['ZFJP_ja'],
        'ZFRO' => ['ZFRO_ro'],
        'ZFMX01' => ['ZFMX01_es'],
    ];

    /**
     * 构造函数
     *
     * @param string $siteCode 站点编码
     * @param string $pipeline 国家站点编码
     * @param string $lang 语言简码
     */
    public function __construct($siteCode, $pipeline, $lang)
    {
        parent::__construct($siteCode, $lang);

        $this->transformer = new ZafulTransformer($siteCode, $pipeline, $lang);
        $this->pipeline = $pipeline;

        // 测试环境引以色列站的索引和线上环境不一样，单独处理
       /* if (AppHelpers::isTestEnv()) {
            self::$supportDomain['ZFIL'] = ['ZFIL_he'];
        }*/
    }

    /**
     * @param array $params 搜索参数
     *
     * - 格式：
     * - page_num 页码
     * - page_size 分页大小
     * - sort_by 排序
     * - identify 用户cookieid
     * - rule_id 商品运营平台规则ID
     * - aggregations 要聚合的字段名称列表
     * - filters 要过滤的属性列表
     */
    public function buildRequestParams($params)
    {
        $this->apiParams = [];

        /*------------------------------------- 公共 ------------------------------------------------*/
        $this->apiParams['domain'] = $this->getDomain($this->pipeline, $this->lang);
        $this->buildCommandParams($params);

        // sku同款过滤
        $this->apiParams['collapse'] = [
            'field' => 'groupColorGoodsId'
        ];

        $aggregations = $filters = [];
        /*------------------------------------- 聚合 -------------------------------------------------*/
        if (!empty($params[self::PARAM_NAME_AGGREGATIONS]) && is_array($params[self::PARAM_NAME_AGGREGATIONS])) {
            foreach ($params[self::PARAM_NAME_AGGREGATIONS] as $attr => $value) {
                if (SearchParamBuilder::ATTR_FIELD_NAME_CATEGORY === $attr) {
                    $aggregations[] = $this->getCategoryAggregation();
                } elseif (SearchParamBuilder::ATTR_FIELD_NAME_COLOR === $attr) {
                    $aggregations[] = [
                        'field' => SearchParamBuilder::ATTR_FIELD_NAME_COLOR,
                        'size' => 100,
                        'key' => $value,
                        'stat' => 'distinct'
                    ];
                } elseif (SearchParamBuilder::ATTR_FIELD_NAME_SIZE === $attr) {
                    $aggregations[] = [
                        'field' => SearchParamBuilder::ATTR_FIELD_NAME_SIZE,
                        'size' => 100,
                        'key' => $value,
                        'stat' => 'distinct'
                    ];
                } elseif (SearchParamBuilder::ATTR_FIELD_NAME_PRICE === $attr) {
                    if (is_array($value)) {
                        $aggregations[] = [
                            'field' => SearchParamBuilder::ATTR_FIELD_NAME_PRICE,
                            'type' => 'ranges',
                            'stat' => 'distinct',
                            'values' => $value
                        ];
                    }
                } elseif (SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_VALUE === $attr) {
                    $_attrValues = is_string($value) ? [$value] : $value;
                    foreach ($_attrValues as $_attrValue) {
                        $aggregations[] = [
                            'field' => SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_VALUE,
                            'size' => 100,
                            'key' => $_attrValue,
                            'stat' => 'distinct',
                            'filters' => [
                                [
                                    'field' => SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_NAME,
                                    'values' => [$_attrValue]
                                ]
                            ]
                        ];
                    }
                }
            }
        }

        !empty($aggregations) && $this->apiParams[self::PARAM_NAME_AGGREGATIONS] = $aggregations;

        /*--------------------------------------- 过滤 ---------------------------------------*/
        // 过滤没有库存的商品
        $filters[] = [
            'field' => 'stockFlag',
            'values' => [1]
        ];

        // 商品运营平台规则ID
        if (!empty($params['rule_id'])) {
            $filters[] = [
                'field' => 'grids',
                'values' => explode(',', $params['rule_id'])
            ];
        }

        //国家简码过滤蜘蛛侠商品
        $parser = isset($params['parserModel']) ? $params['parserModel'] : NativePageInfo::PARSE_MODEL_PUBLISH;
        //判断编辑模式为发布模式并且不符合条件的国家简码才添加商品过滤添加
        if ($parser == NativePageInfo::PARSE_MODEL_PUBLISH && !in_array(COUNTRY, config('filter.skuFilter.allowCountry'))) {
            $filters[] = [
              'field' => 'tags',
              'values' => config('filter.skuFilter.skuTags'),
              'type'  =>  'mustNot'
            ];
        }

        if (!empty($params[self::PARAM_NAME_FILTERS]) && is_array($params[self::PARAM_NAME_FILTERS])) {
            $targets = $this->getAggTarget();

            foreach ($params[self::PARAM_NAME_FILTERS] as $attr => $values) {
                if (SearchParamBuilder::ATTR_FIELD_NAME_CATEGORY === $attr) {
                    $_catFilters = $this->getCategoryFilter($values);
                    foreach ($_catFilters as $_filter) {
                        $filters[] = $_filter;
                    }
                } elseif (SearchParamBuilder::ATTR_FIELD_NAME_PRICE === $attr) {
                    $filters[] = [
                        'field' => $attr,
                        'values' => $values,
                        'type' => 'range'
                    ];
                } else {
                    if ($attr === SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_NAME) {
                        foreach ($values as $attrName => $_values) {
                            $filters[] = [
                                'field' => SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_NAME,
                                'values' => [$attrName],
                                'operator' => 'must',
                                'pair' => $attrName,
                                'aggsTarget' => $targets
                            ];
                            $filters[] = [
                                'field' => SearchParamBuilder::ATTR_FIELD_NAME_SKU_ATTR_VALUE,
                                'values' => $_values,
                                'operator' => 'must',
                                'pair' => $attrName,
                                'aggsTarget' => $targets
                            ];
                        }
                    } else {
                        $filters[] = [
                            'field' => $attr,
                            'values' => $values,
                            'aggsTarget' => $targets
                        ];
                    }
                }
            }
        }

        !empty($filters) && $this->apiParams[self::PARAM_NAME_FILTERS] = $filters;

        /*-------------------------------------- 排序 ----------------------------------------*/
        if (!empty($params[self::PARAM_NAME_SORT_BY])) {
            $sortBy = $params[self::PARAM_NAME_SORT_BY];
            $supportSorts = $this->getSupportSorts();
            if (in_array($sortBy, $supportSorts, true)) {
                if (SearchParamBuilder::SORT_BY_RECOMMEND === $sortBy) {
                    // 选项开关
                    $this->apiParams['options'] = [
                        'query.rerank.enable' => true,
                        'query.sort.solution' => 'category',
                        'query.sort.scene' => 'geshopcms',
                        'search.branch.select' => 'refine_cat'
                    ];
                    if (AppHelpers::isStagingEnv()) {//ES的预发布索引参数
                        $this->apiParams['options']['preReleaseDomain'] = 'pre_' . $this->getDomain($this->pipeline,
                            $this->lang);
                    }

                    // 分流id, 用于AB测试
                    $this->apiParams['identify'] = $params['identify'] ?? '';

                    // 用户唯一标识，推荐算法统计使用(对应大数据od)
                    $this->apiParams['cookie'] = $params['cookie'] ?? '';

                    // 用户国家编码
                    $this->apiParams['countryCode'] = $params['countryCode'] ?? 'HK';

                } else {
                    $this->apiParams['sorts'] = self::BUSINESS_SORT_REGULAR[$sortBy];
                }
            }
        }
    }

    /**
     * 获取ES索引名称
     *
     * @param string $pipeline
     * @param string $lang
     * @return string
     */
    private function getDomain($pipeline, $lang)
    {
        $domain = sprintf('%s_%s', $pipeline, $lang);
        if (!isset(self::$supportDomain[$pipeline])) {
            $logFormat = '没有找到国家站简码[%s]的ES索引配置, 使用默认规则索引名称[%s]';
            ges_error_log(__CLASS__, $logFormat, $pipeline, $domain);
            return $domain;
        }

        if (in_array($domain, self::$supportDomain[$pipeline], true)) {
            return $domain;
        }
        return self::$supportDomain[$pipeline][0];
    }
}
