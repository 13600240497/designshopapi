<?php
namespace App\Services\NativePage\UiTplParser;

use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParserAssistant;

/**
 * 非原生页面(PC/WAP)接入商品运营平台(支持排序算法和智能推荐功能)数据解析器
 *
 * @author TianHaisen
 */
class Web_biComponentParser extends AbstractUiTplAsyncApiParser
{
    /** @var string HTTP参数名称 - 排序名称,如:hot */
    const HTTP_PARAM_SORT_ID = 'sort_id';

    /** @var string HTTP参数名称 - 分页功能，每页数据大小 */
    const HTTP_PARAM_PAGE_SIZE = 'page_size';

    /**
     * @inheritDoc
     */
    public function init(PageUiAsyncDataParser $pageParser, $componentId)
    {
        parent::init($pageParser, $componentId);
        $this->isShowPromotion = true;
    }

    /**
     * @inheritdoc
     */
    public function buildApiRequestInfo()
    {
        $uiSkuData = $this->getSkuData();
        if (!empty($uiSkuData) && is_array($uiSkuData)) {
            $sortId = $this->getHttpParam(self::HTTP_PARAM_SORT_ID);
            $pageSize = (int)$this->getHttpParam(self::HTTP_PARAM_PAGE_SIZE, 20);

            $esParamBuilder = $this->pageParser->getEsParamBuilder($this->componentId);
            !empty($sortId) && $esParamBuilder->sort($sortId);
            !empty($pageSize) && $esParamBuilder->pageSize($pageSize);

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
            UiTplParserAssistant::transformWebSingleRuleSkuData($this, $this->componentId);
        }
    }

    /**
     * @inheritdoc
     */
    public function transformAppData()
    {

    }
}
