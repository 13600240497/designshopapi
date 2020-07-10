<?php
namespace App\Services\NativePage\UiTplParser\Sku\Provider;

use App\Base\KeyConstants;
use App\Base\ApiConstants;
use App\Base\GoodsConstants;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 商品运营平台系统商品信息提供者
 *
 * @author TianHaisen
 */
class SopSkuProvider extends AbstractSkuProvider
{
    /** @var string 配置键名 - 商品运营平台规则ID */
    const KEY_SOP_RULE_ID = 'sop_rule_id';

    /** @var array 已使用商品运营平台规则ID */
    private $usedSopRuleIds = [];

    /**
     * @inheritDoc
     *
     * Es查询时会根据这里的参数构建查询,参数详细信息查看 AbstractEsSearch::buildRequestParams
     * @see \App\Services\EsSearch\ZafulEsSearch::buildRequestParams()
     */
    public function buildAsyncApiInfo($skuInfo)
    {
        // 配置数据不完整或类型不对跳过
        if (!isset($skuInfo[self::KEY_SOP_RULE_ID]) || empty($skuInfo[self::KEY_SOP_RULE_ID])) {
            return [];
        }

        // 单个组件商品运营平台规则ID，不能重复使用
        $sopRuleId = (int)$skuInfo[self::KEY_SOP_RULE_ID];
        if (in_array($sopRuleId, $this->usedSopRuleIds, true)) {
            return [];
        }

        $this->usedSopRuleIds[] = $sopRuleId;

        // 设置商品运营平台规则ID和当前页码
        $esParamBuilder = $this->pageParser->getEsParamBuilder($this->uiTplParser->getComponentId());
        $esParamBuilder->ruleId($sopRuleId);
        $pageNum = (int)$this->uiTplParser->getHttpParam(AbstractUiTplAsyncApiParser::HTTP_PARAM_PAGE_NO, 0);
        ($pageNum > 0) && $esParamBuilder->pageNum($pageNum);

        // 以下参数用于ES搜索人工智能推荐算法使用
        $cookieId = $this->uiTplParser->getHttpParam(AbstractUiTplAsyncApiParser::HTTP_PARAM_COOKIE_ID);
        $btsUniqueId = $this->uiTplParser->getHttpParam(AbstractUiTplAsyncApiParser::HTTP_PARAM_BTS_UNIQUE_ID);
        $countryCode = $this->uiTplParser->getHttpParam(AbstractUiTplAsyncApiParser::HTTP_PARAM_COUNTRY_CODE); // 访问用户国家简码
        $agent = $this->uiTplParser->getHttpParam(AbstractUiTplAsyncApiParser::HTTP_PARAM_AGENT); // 来源平台
        !empty($cookieId) && $esParamBuilder->cookie($cookieId);
        !empty($btsUniqueId) && $esParamBuilder->identify($btsUniqueId);
        !empty($countryCode) && $esParamBuilder->countryCode($countryCode);
        !empty($agent) && $esParamBuilder->agent($agent);

        return [
            SkuDataParser::KEY_API_NAME     => ApiConstants::API_NAME_ES_SEARCH,
            SkuDataParser::KEY_API_PARAMS   => $esParamBuilder->build()
        ];
    }

    /**
     * @inheritDoc
     */
    public function transformResult(&$esResultRefer)
    {
        $esSearch = $this->pageParser->getEsSearch();
        $asyncInfo = [
            KeyConstants::GOODS_LIST => $esSearch->transformGoodsInfo($esResultRefer),
            KeyConstants::PAGINATION => $esSearch->transformPaginationInfo($esResultRefer)
        ];

        // 设置需求补充价格的商品SKU
        if (!empty($asyncInfo[KeyConstants::GOODS_LIST])) {
            $goodsSkuList = array_column($asyncInfo[KeyConstants::GOODS_LIST], GoodsConstants::GOODS_SN);
            $this->pageParser->getAsyncDataSupplement()->setGoodsSKU($goodsSkuList);
        }

        // 如果ES分流信息，没有设置，尝试获取ES分流信息
        if (!$this->pageParser->hasBtsResultInfo()) {
            $btsInfo = $esSearch->transformBtsInfo($esResultRefer);
            if (!empty($btsInfo)) {
                $this->pageParser->setBtsResultInfo($btsInfo);
            }
        }

        return $asyncInfo;
    }
}
