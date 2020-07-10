<?php
namespace App\Services\NativePage\UiTplParser;

use App\Base\KeyConstants;
use App\Services\EsSearch\SearchParamBuilder;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParserAssistant;

/**
 * 智能商品列表(模板1)数据解析器
 *
 * @author TianHaisen
 */
class U000249_template1Parser extends AbstractUiTplAsyncApiParser
{
    /**
     * @inheritDoc
     */
    public function init(PageUiAsyncDataParser $pageParser, $componentId)
    {
        parent::init($pageParser, $componentId);

        $this->isTransStandardGetDetailApiResult = false;
    }

    /**
     * 获取默认排序规则配置
     *
     * @return string
     */
    private function getSortById()
    {
        $defaultSortById = $this->getSettingValue('sort');
        if (empty($defaultSortById)) {
            return SearchParamBuilder::SORT_BY_RECOMMEND;
        }
        return $defaultSortById;
    }

    /**
     * @inheritdoc
     */
    public function buildApiRequestInfo()
    {
        $uiSkuData = $this->getSkuData();
        if (!empty($uiSkuData) && is_array($uiSkuData)) {
            $esParamBuilder = $this->pageParser->getEsParamBuilder($this->componentId);

            // 默认排序
            $defaultSortById = $this->getSortById();
            $esParamBuilder->sort($defaultSortById);

            // 分页大小
            $pageConfig = $this->getSettingValue('page');
            if (is_array($pageConfig) && isset($pageConfig['status'])) {
                $pageStatus = (int)$pageConfig['status'];

                if (1 === $pageStatus) { // 开启分页功能，默认分页大小20
                    $esParamBuilder->pageSize(20);
                } else {
                    $esParamBuilder->pageSize($pageConfig['pageSize'] ?? 20);
                }
            }

            return $this->buildAsyncApiInfoBySkuData($uiSkuData);
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function transformApiResult()
    {
        if ($this->hasSkuData()) {
            $this->transformResultBySkuData();

            $asyncDataListRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
            if (!is_array($asyncDataListRefer)
                || empty($asyncDataListRefer)
                || (count($asyncDataListRefer) > 1)
            ) {
                return;
            }

            // 没有开启分页功能，如果后台返回数据大于分页大小，直接把总数改为分页大小，
            $pageConfig = $this->getSettingValue('page');
            if (!empty($pageConfig)) {
                $pageStatus = (int)$pageConfig['status'];
                if (1 !== $pageStatus
                    && isset($asyncDataListRefer[self::UI_ASYNC_SKU_INFO][0][KeyConstants::PAGINATION])
                ) {
                    $paginationRefer = & $asyncDataListRefer[self::UI_ASYNC_SKU_INFO][0][KeyConstants::PAGINATION];
                    $totalCount = $paginationRefer[KeyConstants::TOTAL_COUNT];
                    $pageSize = (int)$pageConfig['pageSize'];
                    if ((int)$totalCount > $pageSize) {
                        $paginationRefer[KeyConstants::TOTAL_COUNT] = $pageSize;
                    }
                    unset($paginationRefer);
                }
            }
            unset($asyncDataListRefer);
        }
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
        if ($this->hasSkuData()) {
            UiTplParserAssistant::transformWebAllRuleSkuDataOldFormat($this, $this->componentId);
        }
    }

    /**
     * @inheritdoc
     */
    public function transformAppData()
    {
        if ($this->hasSkuData()) {
            UiTplParserAssistant::transformAppSingleRuleSkuData($this, $this->componentId);
        }
    }
}
