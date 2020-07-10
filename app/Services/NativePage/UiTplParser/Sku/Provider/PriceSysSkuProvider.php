<?php
namespace App\Services\NativePage\UiTplParser\Sku\Provider;

use App\Base\KeyConstants;
use App\Base\ApiConstants;
use App\Helpers\AppHelpers;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 服装站点价格系统商品信息提供者
 *
 * @author TianHaisen
 */
class PriceSysSkuProvider extends AbstractSkuProvider
{
    /** @var string 配置键名 - 服装价格体系队列ID列表 */
    const KEY_PRICE_SYS_IDS = 'price_sys_ids';

    /**
     * @inheritDoc
     */
    public function buildAsyncApiInfo($skuInfo)
    {
        // 价格系统队列ID列表
        if (!isset($skuInfo[self::KEY_PRICE_SYS_IDS]) || empty($skuInfo[self::KEY_PRICE_SYS_IDS])) {
            return [];
        }

        $pageInfo = $this->pageParser->getPageInfo();
        $apiParams = [
            'content' =>[
                'pipeline' => $pageInfo->getPipeline(),
                'lang' => $pageInfo->getLang(),
                'price_sys_ids' => $skuInfo[self::KEY_PRICE_SYS_IDS],
            ],
        ];
        //蜘蛛侠商品过滤参数  2020-05-15
        if ($this->pageParser->getParseModel() == NativePageInfo::PARSE_MODEL_PUBLISH) {
            $apiParams['content']['regionCode'] = COUNTRY;
        }
        $apiParams['content'] =  AppHelpers::jsonEncode($apiParams['content']);

        return [
            SkuDataParser::KEY_API_NAME     => ApiConstants::API_NAME_GET_TSK_GOODS_DETAIL_BY_PRICE_SYS_IDS,
            SkuDataParser::KEY_API_PARAMS   => $apiParams
        ];
    }

    /**
     * @inheritDoc
     */
    public function transformResult(&$apiResultRefer)
    {
        $apiResultTransformer = $this->pageParser->getSiteApiResultTransformer();
        $uiAsyncInfo = $apiResultTransformer->transGetTskGoodsDetailByPriceSysIds($apiResultRefer);
        return [
            KeyConstants::GOODS_LIST    => $uiAsyncInfo[KeyConstants::GOODS_LIST] ?? [],
            KeyConstants::TSK_INFO      => $uiAsyncInfo[KeyConstants::TSK_INFO] ?? [],
        ];
    }
}
