<?php
namespace App\Services\NativePage;

use App\Base\KeyConstants;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 组件模板数据解析是使用公共方法
 *
 * @author TianHaisen
 */
class UiTplParserAssistant
{
    /**
     * 转换Web组件所有规则SKU数据为老格式
     *
     * @param AbstractUiTplAsyncApiParser $uiTplParser 模板解析器
     * @param int $componentId 组件ID
     */
    public static function transformWebAllRuleSkuDataOldFormat($uiTplParser, $componentId)
    {
        $pageParser = $uiTplParser->getPageParser();
        $asyncDataListRefer = & $pageParser->getComponentAsyncDataRefer($componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        $uiSkuKey = AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO;
        if (isset($asyncDataListRefer[$uiSkuKey])) {
            $asyncInfo = [];
            foreach ($asyncDataListRefer[$uiSkuKey] as & $ruleSkuInfoRefer) {
//                $ruleAsyncInfo = [
//                    SkuDataParser::KEY_SKU_RULE_ID              => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_ID],
//                    SkuDataParser::KEY_SKU_RULE_TYPE            => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_TYPE],
//                    SkuDataParser::KEY_SKU_RULE_COMPONENT_ID    => $ruleSkuInfoRefer[SkuDataParser::KEY_SKU_RULE_COMPONENT_ID],
//                    'goodsInfo'                                 => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST]
//                ];
//
//                if (isset($ruleSkuInfoRefer[KeyConstants::PAGINATION])) {
//                    $ruleAsyncInfo[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION];
//                }

                $ruleAsyncInfo = [];
                foreach ($ruleSkuInfoRefer as $key => $value) {
                    if ($key === KeyConstants::GOODS_LIST) {
                        $ruleAsyncInfo['goodsInfo'] = & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST];
                    } elseif ($key === KeyConstants::PAGINATION) {
                        $ruleAsyncInfo[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION];
                    } else {
                        $ruleAsyncInfo[$key] = $value;
                    }
                }

                $asyncInfo[$uiSkuKey][] = $ruleAsyncInfo;
            }

            $pageParser->getWebDataTransformer()->setComponentAsyncInfo($componentId, $asyncInfo);
        }
    }

    /**
     * 转换Web组件第一个规则的SKU数据
     *
     * @param AbstractUiTplAsyncApiParser $uiTplParser 模板解析器
     * @param int $componentId 组件ID
     */
    public static function transformWebSingleRuleSkuData($uiTplParser, $componentId)
    {
        $pageParser = $uiTplParser->getPageParser();
        $asyncDataListRefer = & $pageParser->getComponentAsyncDataRefer($componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        $uiSkuKey = AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO;
        if (isset($asyncDataListRefer[$uiSkuKey])) {
            $ruleSkuInfoRefer = & $asyncDataListRefer[$uiSkuKey][0];
            $asyncInfo = [
                KeyConstants::GOODS_LIST => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST],
            ];
            if (isset($ruleSkuInfoRefer[KeyConstants::PAGINATION])) {
                $asyncInfo[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION];
            }
            $pageParser->getWebDataTransformer()->setComponentAsyncInfo($componentId, $asyncInfo);
        }
    }

    /**
     * 转换APP组件第一个规则的SKU数据
     *
     * @param AbstractUiTplAsyncApiParser $uiTplParser 模板解析器
     * @param int $componentId 组件ID
     */
    public static function transformAppSingleRuleSkuData($uiTplParser, $componentId)
    {
        $pageParser = $uiTplParser->getPageParser();
        $asyncDataListRefer = & $pageParser->getComponentAsyncDataRefer($componentId);
        if (!is_array($asyncDataListRefer) || empty($asyncDataListRefer)) {
            return;
        }

        if (isset($asyncDataListRefer[AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO])) {
            $ruleSkuInfoRefer = & $asyncDataListRefer[AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO][0];

            if ($uiTplParser->isVersionV2()) {
                $asyncInfo = [
                    KeyConstants::GOODS_LIST => & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST] // 商品数据
                ];
                if (isset($ruleSkuInfoRefer[KeyConstants::PAGINATION])) {
                    $asyncInfo[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION]; // 分页数据
                }
                $pageParser->getAppDataTransformer()->setComponentAsyncInfo($componentId, $asyncInfo);
            } else {
                $appDataRefer = & $pageParser->getAppDataTransformer()->getComponentDataRefer($componentId);
                if (!is_array($appDataRefer) || empty($appDataRefer)) {
                    return;
                }

                // 商品数据
                $appDataRefer['list'] = & $ruleSkuInfoRefer[KeyConstants::GOODS_LIST];

                // 分页数据
                if (isset($ruleSkuInfoRefer[KeyConstants::PAGINATION])) {
                    $appDataRefer[KeyConstants::PAGINATION] = & $ruleSkuInfoRefer[KeyConstants::PAGINATION];
                }

                unset($appDataRefer);
            }

            unset($ruleSkuInfoRefer);
        }
    }
}
