<?php
namespace App\Services\NativePage\UiTplParser;

use App\Helpers\AppHelpers;
use App\Helpers\BeanHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\Site\IBaseDataTransformer;
use App\Services\EsSearch\SearchParamBuilder;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;
use App\Services\NativePage\AppDataTransformer;

/**
 * 属性筛选(模板1)数据解析器
 *
 * @author TianHaisen
 */
class U000248_template1Parser extends AbstractUiTplAsyncApiParser
{
    /** @var array 支持排序列表 */
    private $sortByList;

    /**
     * @inheritDoc
     */
    public function init(PageUiAsyncDataParser $pageParser, $componentId)
    {
        parent::init($pageParser, $componentId);

        $this->sortByList = $this->pageParser->getEsSearch()->getSortByList();
    }

    /**
     * 获取组件关联商品列表组件ID
     *
     * @return int
     */
    private function getRelatedGoodsListComponentId()
    {
        return (int)$this->getSettingValue('connection', 0);
    }

    /**
     * 获取关联组件默认排序规则配置
     *
     * @return string
     */
    private function getRelatedGoodsListSortById()
    {
        $relatedComponentId = $this->getRelatedGoodsListComponentId();
        $defaultSortById = $this->getPageInfo()->getComponentSettingValue($relatedComponentId, 'sort');
        if (empty($defaultSortById)) {
            return SearchParamBuilder::SORT_BY_RECOMMEND;
        }
        return $defaultSortById;
    }

    /**
     * 获取商品列表排序
     *
     * @return string
     */
    private function getSortId()
    {
        $sortByIds = array_column($this->sortByList, 'item_id');
        $sortId = $this->getHttpParam('sort_id'); // 排序id字符串，如:hot
        // 传入参数优先
        if (!empty($sortId) && in_array($sortId, $sortByIds, true)) {
            return $sortId;
        }

        // 没有参数排序，使用组件配置排序
        return $this->getRelatedGoodsListSortById();
    }

    /**
     * @inheritdoc
     */
    public function buildApiRequestInfo()
    {
        $relatedComponentId = $this->getRelatedGoodsListComponentId();
        if ($relatedComponentId > 0 && $this->getPageInfo()->hasComponent($relatedComponentId)) {
            $relatedUiSkuData = $this->getPageInfo()->getComponentSkuData($relatedComponentId);
            if (!empty($relatedUiSkuData) && is_array($relatedUiSkuData)) {
                $categoryId = (int)$this->getHttpParam('category_id', 0); // 分类id字符串用逗号隔开，如:123， 456..
                $sortId = $this->getSortId();
                $refineId = $this->getHttpParam('refine_id'); // refine筛选json字符串
                $price_min = $this->getHttpParam('price_min', -1); // 最大价格字
                $price_max = $this->getHttpParam('price_max', -1); // 最小价格字

                $aggAttrs = $this->getAttrs($categoryId);
                $filters = $this->getFilters($refineId);
                $isFirst = true; // 是否第一次加载
                $esParamBuilder = $this->pageParser->getEsParamBuilder($this->componentId);

                // 去关联商品列表分页大小数据
                $pageConfig = $this->getPageInfo()->getComponentSettingValue($relatedComponentId, 'page');
                if (is_array($pageConfig) && isset($pageConfig['status'])) {
                    $pageStatus = (int)$pageConfig['status'];
                    if ($pageStatus === 1) { // 开启分页功能，默认分页大小20
                        $esParamBuilder->pageSize(20);
                    } else {
                        $esParamBuilder->pageSize($pageConfig['pageSize'] ?? 20);
                    }
                }

                // 属性组件相关尝试设置
                !empty($sortId) && $esParamBuilder->sort($sortId);
                $esParamBuilder->aggAttrs($aggAttrs);
                $isFirst && $esParamBuilder->aggCategory();

                ($categoryId > 0) && $esParamBuilder->filterCategory($categoryId);
                !empty($filters) && $esParamBuilder->filterAttrs($filters);
                if (is_numeric($price_min)
                    && is_numeric($price_max)
                    && ($price_min != $price_max)
                    && ((int)$price_min > -1)
                    && ((int)$price_max > -1)
                ) {
                    $esParamBuilder->filterPrice($price_min, $price_max);
                }

                return $this->buildAsyncApiInfoBySkuData($relatedUiSkuData);
            }
        }

        return [];
    }

    /**
     * 根据HTTP参数，解析过滤条件
     *
     * @param mixed $refineId
     * @return array
     */
    private function getFilters($refineId)
    {
        if (is_array($refineId)) {
            return $refineId;
        }

        $filters = [];
        if (is_string($refineId)
            && !empty($refineId)
        ) {
            $filters = AppHelpers::jsonDecode($refineId, true);
            !is_array($filters) &&  $filters = [];
        }
        return $filters;
    }

    /**
     * 获取属性列表，并将站点的属性字段转换为geshop通用格式
     *
     * @param int $categoryId 分类ID
     * @return array
     */
    private function getAttrs($categoryId)
    {
        $attrs = [];
        try {
            if ($categoryId > 0) {
                $siteCode = $this->getPageInfo()->getSiteCode();
                $lang = $this->getPageInfo()->getLang();
                $baseDataProvider = BeanHelpers::getBaseDataProvider($siteCode);
                $attrs = $baseDataProvider->getTransformer()->getCategoryAttrsById($baseDataProvider, $lang,
                    $categoryId);
            }
        } catch (ApiRequestException $e) {
            ges_error_log(__CLASS__, '调用站点站点分类和属性接口错误, 使用默认属性。');
        }

        if (empty($attrs)) {
            return $this->getDefaultAttrs();
        }
        return $attrs;
    }

    /**
     * 获取默认属性
     *
     * @return array
     */
    private function getDefaultAttrs()
    {
        $websiteCode = $this->getPageInfo()->getWebsiteCode();
        $attrs = config(sprintf('es.%s.defaultAttr', $websiteCode));
        foreach ($attrs as &$attrInfoRefer) {
            $attrInfoRefer['title'] = __('common.site_attr_'. strtolower($attrInfoRefer['title']));
        }
        return $attrs;
    }

    /**
     * @inheritdoc
     */
    public function associationProcessing()
    {
        $relatedComponentId = $this->getRelatedGoodsListComponentId();
        if ($relatedComponentId > 0 && $this->getPageInfo()->hasComponent($relatedComponentId)) {
            $this->pageParser->removeComponentRequestApiInfo($relatedComponentId);
        }
    }

    /**
     * @inheritdoc
     */
    public function transformApiResult()
    {
        // 将商品列表返回到目标组件上面
        $relatedComponentId = $this->getRelatedGoodsListComponentId();
        if ($relatedComponentId > 0 && $this->getPageInfo()->hasComponent($relatedComponentId)) {
            $this->transformResultBySkuData($relatedComponentId);
        }

        // API请求信息
        $apiInfoList = $this->pageParser->getComponentRequestApiInfo($this->componentId);
        if (empty($apiInfoList) || !is_array($apiInfoList)) {
            return;
        }

        // API请求返回
        $apiResultListRefer = & $this->pageParser->getComponentApiResultRefer($this->componentId);
        if (empty($apiResultListRefer) || !is_array($apiResultListRefer) || count($apiResultListRefer) > 1) {
            return;
        }

        $uiAsyncInfo = [];
        $esSearch = $this->pageParser->getEsSearch();
        $_sortByList = $this->sortByList;

        // 获取关联商品默认排序配置, 如果配置默认排序，把默认排序放在列表第一位置
        $defaultSortById = $this->getRelatedGoodsListSortById();
        if (!empty($defaultSortById)) {
            $indexSortByList = array_column($_sortByList, null, 'item_id');
            if (isset($indexSortByList[$defaultSortById]) && ($defaultSortById !== $_sortByList[0]['item_id'])) {
                $defaultItem = $indexSortByList[$defaultSortById];
                unset($indexSortByList[$defaultSortById]);
                array_unshift($indexSortByList, $defaultItem);
                $_sortByList = array_values($indexSortByList);
            }
        }

        // 组件异步数据
        foreach ($apiResultListRefer as $apiId => & $esResultRefer) {
            $esParams = $apiInfoList[$apiId][SkuDataParser::KEY_API_PARAMS] ?? [];
            $uiAsyncInfo['sort_list'] = $_sortByList;
            $uiAsyncInfo['category_list'] = $esSearch->transformCategoryInfo($esResultRefer);
            $uiAsyncInfo['refine_list'] = $esSearch->transformAttrInfo($esParams, $esResultRefer);
        }

        unset($apiResultListRefer, $esResultRefer);
        $this->pageParser->setComponentAsyncData($this->componentId, $uiAsyncInfo);
    }

    /**
     * @inheritdoc
     */
    public function supplementaryAsyncData()
    {
        if ($this->hasSkuData()) {
            $this->supplementaryAsyncDataBySkuData();
        }
    }

    /**
     * @inheritdoc
     */
    public function transformWebData()
    {
        $asyncDataListRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        $webAsyncInfo = [
            'sort_list'     => & $asyncDataListRefer['sort_list'],
            'category_list' => & $asyncDataListRefer['category_list'],
            'refine_list'   => & $asyncDataListRefer['refine_list'],
        ];
        $this->pageParser->getWebDataTransformer()->setComponentAsyncInfo($this->componentId, $webAsyncInfo);
    }

    /**
     * @inheritdoc
     */
    public function transformAppData()
    {
        $asyncDataListRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        // APP端在属性列表第一个位置添加一个价格筛选属性的占位项
        $refineList = $asyncDataListRefer['refine_list'];
        array_unshift($refineList, [
            'item_id' => 'Price',
            'item_type' => IBaseDataTransformer::ATTR_SHOW_TYPE_PRICE,
            'item_title' => __('common.site_attr_price'),
            'price_max' => 200,
            'price_min' => 0,
            'item_child' => []
        ]);

        // 将异步数据信息，添加APP的component_data下
        $appAsyncInfo = [
            'sort_list'     => & $asyncDataListRefer['sort_list'],
            'category_list' => & $asyncDataListRefer['category_list'],
            'refine_list'   => $refineList
        ];

        if ($this->isVersionV2()) {
            $this->pageParser->getAppDataTransformer()->setComponentAsyncInfo($this->componentId, $appAsyncInfo);
        } else {
            $appDataRefer = & $this->pageParser->getAppDataTransformer()->getComponentDataRefer($this->componentId);
            if (!is_array($appDataRefer) || !isset($appDataRefer[AppDataTransformer::UI_KEY_COMPONENT_DATA])) {
                return;
            }

            foreach ($appAsyncInfo as $_key => $asyncInfo) {
                $appDataRefer[AppDataTransformer::UI_KEY_COMPONENT_DATA][$_key] = $asyncInfo;
            }
            unset($appDataRefer);
        }

        unset($asyncDataListRefer);
    }
}
