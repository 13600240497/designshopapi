<?php
namespace App\Services\EsSearch;

/**
 * ES搜索参数构建器
 *
 * @author TianHaisen
 */
class SearchParamBuilder
{
    /** @var string 排序 - 推荐 */
    const SORT_BY_RECOMMEND = 'recommend';

    /** @var string 排序 - 热度 */
    const SORT_BY_HOT = 'hot';

    /** @var string 排序 -  */
    const SORT_BY_TRENDING = 'trending';

    /** @var string 排序 - 新品 */
    const SORT_BY_NEW = 'new';

    /** @var string 排序 - 价格低到高 */
    const SORT_BY_PRICE_LOW_TO_HIGH = 'price-low-to-high';

    /** @var string 排序 - 价格高到底 */
    const SORT_BY_PRICE_HIGH_TO_LOW = 'price-high-to-low';

    /** @var string 属性字段名称 - 商品分类,这个属性聚合和过滤处理比较特殊 */
    const ATTR_FIELD_NAME_CATEGORY = 'categoryAttr';

    /** @var string  属性字段名称 - 颜色 */
    const ATTR_FIELD_NAME_COLOR = 'colorAttr';

    /** @var string  属性字段名称 - 尺寸 */
    const ATTR_FIELD_NAME_SIZE = 'sizeAttr';

    /** @var string  属性字段名称 - 价格 */
    const ATTR_FIELD_NAME_PRICE = 'displayPrice';

    /** @var string  属性字段名称 - 常规属性聚合（款式、材质） */
    const ATTR_FIELD_NAME_SKU_ATTR_VALUE = 'skuAttrs.attrValue';
    const ATTR_FIELD_NAME_SKU_ATTR_NAME = 'skuAttrs.attrName';

    /** @var array 属性名称 - 颜色 */
    const ATTR_NAME_COLOR = 'Color';

    /** @var array 属性名称 - 尺寸 */
    const ATTR_NAME_SIZE = 'Size';

    /** @var array 属性名称对应ES搜索字段名称 */
    const ATTR_FILED_NAME_MAP = [
        self::ATTR_NAME_COLOR => self::ATTR_FIELD_NAME_COLOR,
        self::ATTR_NAME_SIZE => self::ATTR_FIELD_NAME_SIZE,
    ];

    /** @var string ES搜索来源平台 */
    private $agent;

    /** @var array 站点的属性列表，用于解析搜索结果，属性解析 */
    private $siteAttrs = [];

    /** @var array 请求参数 */
    private $params = [];

    /** @var array 聚合参数 */
    private $aggregations = [];

    /** @var array 过滤参数 */
    private $filters = [];

    private $sortBy = null;

    /**
     * 常规属性聚合
     *
     * @param string $attrName 常规属性聚合（款式、材质）如: Style
     * @return $this
     */
    public function agg($attrName)
    {
        return $this->_agg(self::ATTR_FIELD_NAME_SKU_ATTR_VALUE, $attrName);
    }

    /**
     * 聚合属性列表
     *
     * @param array $attrs 属性列表
     * - 格式
     * - title 属性搜索值
     * - name 属性名称，用于对应不同ES搜索项
     * - type 2:是颜色，1:是其他
     */
    public function aggAttrs($attrs)
    {
        if (!is_array($attrs) || empty($attrs)) {
            return;
        }

        foreach ($attrs as $attrInfo) {
            $name = $attrInfo['name'];
            if (self::ATTR_NAME_COLOR === $name) {
                $this->aggColor($attrInfo['title']);
            } elseif (self::ATTR_NAME_SIZE === $name) {
                $this->aggSize($attrInfo['title']);
            } else {
                $this->agg($attrInfo['title']);
            }
        }
        $this->siteAttrs = $attrs;
    }

    /**
     * 颜色聚合
     *
     * @param string $colorName 颜色名称注意多语言,如: Color
     * @return $this
     */
    public function aggColor($colorName)
    {
        return $this->_agg(self::ATTR_FIELD_NAME_COLOR, $colorName);
    }

    /**
     * 尺寸聚合
     *
     * @param string $sizeName 尺寸名称注意多语言,如: Size
     * @return $this
     */
    public function aggSize($sizeName)
    {
        return $this->_agg(self::ATTR_FIELD_NAME_SIZE, $sizeName);
    }

    /**
     * 价格聚合
     *
     * @param array $priceList 价格阶梯列表[0, 100, 150]
     * @return $this
     */
    public function aggPrice($priceList)
    {
        if (empty($priceList) || !is_array($priceList)) {
            return $this;
        }

        $size = count($priceList);
        $values = [];
        $from = $priceList[0];
        for ($i = 1; $i < $size; $i++) {
            $to = $priceList[$i];
            $values[] = [
                'key' => 'p'. $i,
                'from' => $from,
                'to' => $to
            ];
            $from = $to;
        }

        return $this->_agg(self::ATTR_FIELD_NAME_PRICE, $values);
    }

    /**
     * 设置商品分类属性聚合
     *
     * @return $this
     */
    public function aggCategory()
    {
        $this->_agg(self::ATTR_FIELD_NAME_CATEGORY, 'category');
        return $this;
    }

    /**
     * 设置过滤条件
     *
     * @param string $attr 属性名称
     * @param array $values 属性值
     * @param bool $isSkuAttr 是否SKU属性
     * @return $this
     */
    public function filter(string $attr, array $values, $isSkuAttr = false)
    {
        if (!empty($attr) && !empty($values)) {
            if ($isSkuAttr) {
                if (!isset($this->filters[self::ATTR_FIELD_NAME_SKU_ATTR_NAME])) {
                    $this->filters[self::ATTR_FIELD_NAME_SKU_ATTR_NAME] = [];
                }
                $this->filters[self::ATTR_FIELD_NAME_SKU_ATTR_NAME][$attr] = $values;
            } else {
                $this->filters[$attr] = $values;
            }
        }
        return $this;
    }

    /**
     * 设置商品分类过滤
     *
     * @param int $categoryId
     * @return $this
     */
    public function filterCategory($categoryId)
    {
        if (is_numeric($categoryId) && (int)$categoryId > 0) {
            $this->filter(self::ATTR_FIELD_NAME_CATEGORY, [$categoryId]);
        }
        return $this;
    }

    /**
     * 颜色过滤
     *
     * @param array $colors
     */
    public function filterColor($colors)
    {
        if (is_array($colors) && !empty($colors)) {
            $this->filter(self::ATTR_FIELD_NAME_COLOR, $colors);
        }
    }

    /**
     * 尺码过滤
     *
     * @param array $sizes
     */
    public function filterSize($sizes)
    {
        if (is_array($sizes) && !empty($sizes)) {
            $this->filter(self::ATTR_FIELD_NAME_SIZE, $sizes);
        }
    }

    /**
     * 价格过滤
     *
     * @param float $from
     * @param float $to
     */
    public function filterPrice($from, $to)
    {
        if (is_numeric($from) && is_numeric($to)) {
            $this->filter(self::ATTR_FIELD_NAME_PRICE, [$from, $to]);
        }
    }

    /**
     * 过滤多个属性
     *
     * @param array $attrs
     */
    public function filterAttrs($attrs)
    {
        if (!is_array($attrs) || empty($attrs)) {
            return;
        }

        foreach ($attrs as $attrName => $values) {
            if (!is_array($values)) {
                continue;
            }

            if (self::ATTR_NAME_COLOR === $attrName) {
                $this->filterColor($values);
            } elseif (self::ATTR_NAME_SIZE === $attrName) {
                $this->filterSize($values);
            } else {
                $this->filter($attrName, $values, true);
            }
        }
    }

    /**
     * 设置商品运营平台规则ID
     *
     * @param int $ruleId 商品运营平台规则ID
     * @return static
     */
    public function ruleId($ruleId)
    {
        if (is_numeric($ruleId) && (int)$ruleId > 0) {
            $this->params['rule_id'] = (int)$ruleId;
        }
        return $this;
    }

    /**
     * 设置 ES搜索来源平台
     * @param string $agent ES搜索来源平台
     */
    public function agent($agent)
    {
        $this->agent = strtolower($agent);
    }

    /**
     * 设置排序
     *
     * @param string $sort 排序名称
     * @return $this
     */
    public function sort($sort)
    {
        $allow = [
            self::SORT_BY_RECOMMEND,
            self::SORT_BY_HOT,
            self::SORT_BY_TRENDING,
            self::SORT_BY_NEW,
            self::SORT_BY_PRICE_LOW_TO_HIGH,
            self::SORT_BY_PRICE_HIGH_TO_LOW,
        ];
        if (!empty($sort) && in_array($sort, $allow, true)) {
            $this->sortBy = $sort;
        }
        return $this;
    }

    /**
     * 设置当前页码
     *
     * @param int $pageNum 页码
     * @return $this
     */
    public function pageNum($pageNum)
    {
        if (is_numeric($pageNum) && (int)$pageNum > 0) {
            $this->params['page_num'] = (int)$pageNum;
        }
        return $this;
    }

    /**
     * 设置分页大小
     *
     * @param int $pageSize 分页大小
     * @return $this
     */
    public function pageSize($pageSize)
    {
        if (is_numeric($pageSize) && (int)$pageSize > 0) {
            $this->params['page_size'] = (int)$pageSize;
        }
        return $this;
    }

    /**
     * 用户唯一标识，推荐算法统计使用(对应大数据od)
     *
     * @param string $cookie 用户唯一标识
     * @return $this
     */
    public function cookie($cookie)
    {
        if (!empty($cookie)) {
            $this->params['cookie'] = $cookie;
        }
        return $this;
    }

    /**
     * 设置 分流id, 用于AB测试
     *
     * @param string $identify 分流id
     * @return $this
     */
    public function identify($identify)
    {
        if (!empty($identify)) {
            $this->params['identify'] = $identify;
        }
        return $this;
    }

    /**
     * 设置访问用户国家简码
     * @param string $code 访问用户国家简码
     * @return $this
     */
    public function countryCode($code)
    {
        if (!empty($code)) {
            $this->params['countryCode'] = $code;
        }
        return $this;
    }

    /**
     * 构建请求参数
     *
     * @return array
     */
    public function build()
    {
        // 排序
        if (empty($this->sortBy)) {
            $this->sortBy = self::SORT_BY_RECOMMEND;
        }
        $this->params[AbstractEsSearch::PARAM_NAME_SORT_BY] = $this->sortBy;

        // ES搜索来源平台
        if (!empty($this->agent)) {
            $this->params[AbstractEsSearch::PARAM_NAME_AGENT] = $this->agent;
        }

        // 聚合
        if (!empty($this->aggregations)) {
            $this->params[AbstractEsSearch::PARAM_NAME_AGGREGATIONS] = $this->aggregations;
        }

        // 过滤
        if (!empty($this->filters)) {
            $this->params[AbstractEsSearch::PARAM_NAME_FILTERS] = $this->filters;
        }

        $this->params[AbstractEsSearch::PARAM_NAME_SITE_ATTRS] = $this->siteAttrs;

        return $this->params;
    }

    /**
     * 设置聚合属性
     *
     * @param array $attrs 属性字段名称和多语言名对称列表, 如： ['colorAttr' => 'Color']
     * @return $this
     */
    private function _agg($attrName, $value)
    {
        if ($attrName === self::ATTR_FIELD_NAME_SKU_ATTR_VALUE) {
            if (!isset($this->aggregations[$attrName])) {
                $this->aggregations[$attrName] = [];
            }
            $this->aggregations[$attrName][] = $value;
        } else {
            $this->aggregations[$attrName] = $value;
        }
        return $this;
    }
}
