<?php
namespace App\Services\NativePage\UiTplParser;

use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParserAssistant;

/**
 * 商品列表(模板1)数据解析器
 *
 * @author TianHaisen
 */
class U000245_template1Parser extends AbstractUiTplAsyncApiParser
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
     * @inheritdoc
     */
    public function buildApiRequestInfo()
    {
        $uiSkuData = $this->getSkuData();
        if (!empty($uiSkuData) && is_array($uiSkuData)) {
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
