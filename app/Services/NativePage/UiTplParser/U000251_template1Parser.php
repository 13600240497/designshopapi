<?php
namespace App\Services\NativePage\UiTplParser;

use App\Base\KeyConstants;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 单时段秒杀(模板1)数据解析器
 *
 * @author TianHaisen
 */
class U000251_template1Parser extends AbstractUiTplAsyncApiParser
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

    }

    /**
     * @inheritdoc
     */
    public function transformWebData()
    {
        $asyncInfoRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncInfoRefer) || empty($asyncInfoRefer)) {
            return [];
        }

        if (isset($asyncInfoRefer[self::UI_ASYNC_SKU_INFO])) {
            $ruleSkuInfoRefer = & $asyncInfoRefer[self::UI_ASYNC_SKU_INFO][0];
//            $ruleAsyncInfo = [
//                SkuDataParser::KEY_SKU_RULE_ID           => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_ID],
//                SkuDataParser::KEY_SKU_RULE_TYPE         => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_TYPE],
//                SkuDataParser::KEY_SKU_RULE_COMPONENT_ID => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_COMPONENT_ID],
//                'goodsInfo'                              => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST], // 商品数据
//                KeyConstants::TSK_INFO                   => & $ruleSkuInfoRefer[KeyConstants::TSK_INFO], // 秒杀信息
//            ];

            $ruleAsyncInfo = [];
            foreach ($ruleSkuInfoRefer as $key => $value) {
                if ($key === KeyConstants::GOODS_LIST) {
                    $ruleAsyncInfo['goodsInfo'] = & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST];
                } elseif ($key === KeyConstants::TSK_INFO) {
                    $ruleAsyncInfo[KeyConstants::TSK_INFO] = & $ruleSkuInfoRefer[KeyConstants::TSK_INFO];
                } else {
                    $ruleAsyncInfo[$key] = $value;
                }
            }

            unset($ruleSkuInfoRefer);
            $asyncInfo = [
                self::UI_ASYNC_SKU_INFO => [$ruleAsyncInfo],
            ];
            $this->pageParser->getWebDataTransformer()->setComponentAsyncInfo($this->componentId, $asyncInfo);
        }
        unset($asyncInfoRefer);
    }

    /**
     * @inheritdoc
     */
    public function transformAppData()
    {
        $asyncInfoRefer = & $this->pageParser->getComponentAsyncDataRefer($this->componentId);
        if (!is_array($asyncInfoRefer) || empty($asyncInfoRefer)) {
            return [];
        }

        if (isset($asyncInfoRefer[self::UI_ASYNC_SKU_INFO])) {
            $ruleSkuInfoRefer = & $asyncInfoRefer[self::UI_ASYNC_SKU_INFO][0];
            $asyncInfo = [
                KeyConstants::GOODS_LIST => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST], // 商品数据
                KeyConstants::TSK_INFO   => & $ruleSkuInfoRefer[KeyConstants::TSK_INFO], // 秒杀信息
            ];
            unset($ruleSkuInfoRefer);
            $this->pageParser->getAppDataTransformer()->setComponentAsyncInfo($this->componentId, $asyncInfo);
        }
        unset($asyncInfoRefer);
    }
}
