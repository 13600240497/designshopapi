<?php
namespace App\Services\NativePage\UiTplParser;

use App\Base\KeyConstants;
use App\Services\EsSearch\SearchParamBuilder;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 商品Tab(模板1)数据解析器
 *
 * @author TianHaisen
 */
class U000256_template1Parser extends AbstractUiTplAsyncApiParser
{
    /** @var int 当前选择Tab项ID */
    private $selectedTabId;

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

            // 获取Tab下数据
            $defaultTabId = $uiSkuData[0][SkuDataParser::KEY_SKU_RULE_ID];
            $this->selectedTabId = $this->getHttpParam('tab_id', $defaultTabId); // tab数据配置项ID

            $uiSkuDataMapping = array_column($uiSkuData, null, SkuDataParser::KEY_SKU_RULE_ID);
            if (!isset($uiSkuDataMapping[$this->selectedTabId])) {
                return [];
            }

            return $this->buildAsyncApiInfoBySkuData([$uiSkuDataMapping[$this->selectedTabId]]);
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
                if ($pageStatus !== 1
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
        $asyncInfo = $this->transformData();
        if (!empty($asyncInfo)) {
            $this->getPageParser()->getWebDataTransformer()->setComponentAsyncInfo($this->componentId, $asyncInfo);
        }
    }

    /**
     * @inheritdoc
     */
    public function transformAppData()
    {
        $asyncInfo = $this->transformData();
        if (!empty($asyncInfo)) {
            $this->getPageParser()->getAppDataTransformer()->setComponentAsyncInfo($this->componentId, $asyncInfo);
        }
    }

    /**
     * 转换组件数据
     *
     * @return array
     */
    private function transformData()
    {
        $asyncDataListRefer = & $this->getPageParser()->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return [];
        }

        $asyncInfo = [];
        $uiSkuKey = AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO;
        if (isset($asyncDataListRefer[$uiSkuKey])) {
            $ruleSkuInfoRefer = & $asyncDataListRefer[$uiSkuKey][0];
            $ruleAsyncInfo = [
                SkuDataParser::KEY_SKU_RULE_ID  => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_ID],
                KeyConstants::GOODS_LIST        => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST],
            ];

            if (isset($ruleSkuInfoRefer[KeyConstants::PAGINATION])) {
                $ruleAsyncInfo[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION];
            }

            $asyncInfo[] = $ruleAsyncInfo;
        }

        return $asyncInfo;
    }
}
