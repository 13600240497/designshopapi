<?php
namespace App\Services\NativePage\UiTplParser;

use App\Services\NativePage\UiTplParserAssistant;

/**
 * Geshop 装修页面数据解析器
 *
 * @author TianHaisen
 */
class Geshop_designParser extends AbstractUiTplAsyncApiParser
{
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

    }
}
