<?php
namespace App\Services\NativePage\UiTplParser\Sku\Provider;

use App\Base\ApiConstants;
use App\Base\KeyConstants;
use App\Helpers\AppHelpers;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 手动输入SKU 商品信息提供者
 *
 * @author TianHaisen
 */
class InputSkuProvider extends AbstractSkuProvider
{
    /** @var string 配置键名 - SKU列表 */
    const KEY_SKU_LIST = 'sku';

    /**
     * @inheritDoc
     */
    public function buildAsyncApiInfo($skuInfo)
    {
        if (!isset($skuInfo[self::KEY_SKU_LIST]) || empty($skuInfo[self::KEY_SKU_LIST])) {
            return [];
        }

        $pageInfo = $this->pageParser->getPageInfo();
        $apiParams = [
            'content' => AppHelpers::jsonEncode([
                'pipeline' => $pageInfo->getPipeline(),
                'lang' => $pageInfo->getLang(),
                'goodsSn' => $skuInfo[self::KEY_SKU_LIST] // SKU列表
            ]),
        ];

        return [
            SkuDataParser::KEY_API_NAME     => ApiConstants::API_NAME_GET_DETAIL,
            SkuDataParser::KEY_API_PARAMS   => $apiParams
        ];
    }

    /**
     * @inheritDoc
     */
    public function transformResult(&$apiResultRefer)
    {
        $apiResultTransformer = $this->pageParser->getSiteApiResultTransformer();
        $isTransStandard = $this->uiTplParser->getTransStandardGetDetailApiResult();
        $uiAsyncInfo = $apiResultTransformer->getGoodsGetDetailGoodsInfo($apiResultRefer, $isTransStandard);
        return [
            KeyConstants::GOODS_LIST => $uiAsyncInfo
        ];
    }
}
