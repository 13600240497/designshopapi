<?php
namespace App\Services\Site\Zaful;

use App\Base\KeyConstants;
use App\Base\GoodsConstants;
use App\Helpers\SiteHelpers;
use App\Helpers\ShopHelpers;
use App\Services\Site\Traits\ZafulBaseTransformerTrait;
use App\Services\Site\AbstractSiteApiResultTransformer;

/**
 * 处理Zaful站点API接口返回
 *
 * @author tianhaishen
 */
class ZafulApiResultTransformer extends AbstractSiteApiResultTransformer
{
    use ZafulBaseTransformerTrait;

    /**
     * @inheritDoc
     */
    public function transGetTskGoodsDetailByPriceSysIds(&$apiResultRefer)
    {
        $result = [];
        if (isset($apiResultRefer['data']['goods_list']) && isset($apiResultRefer['data']['tsk_info'])) {
            $isApp = SiteHelpers::isAppPlatform($this->siteCode);

            foreach ($apiResultRefer['data']['goods_list'] as &$goodsInfoRefer) {
                $detailUrl = $isApp
                    ? $this->getAppGoodsDetailDeepLink($goodsInfoRefer['goods_id'], $goodsInfoRefer['url_title'])
                    : $goodsInfoRefer['detail_url'];

                $marketPrice = ShopHelpers::getGoodsPrice($goodsInfoRefer['market_price']);
                $tskPrice = ShopHelpers::getGoodsPrice($goodsInfoRefer['tsk_price']);

                $stockNum = (int)$goodsInfoRefer['stock_num'];
                $tskTotalNum = (int)$goodsInfoRefer['tsk_total_num'];
                $tskSaleNum = (int)$goodsInfoRefer['tsk_sale_num'];

                // 如果可销售库存小于1，秒杀剩余库存返回 0
                $tskLeftNum = ($stockNum < 1) ? 0 : ($tskTotalNum - $tskSaleNum);
                $tskLeftNum = ($tskLeftNum < 0) ? 0 : $tskLeftNum; // 秒杀可能超卖,防止返回负数

                $result[KeyConstants::GOODS_LIST][] = [
                    GoodsConstants::GOODS_ID        => $goodsInfoRefer['goods_id'],
                    GoodsConstants::GOODS_SN        => $goodsInfoRefer['goods_sn'],
                    GoodsConstants::GOODS_TITLE     => $goodsInfoRefer['goods_title'],
                    GoodsConstants::GOODS_IMG       => $goodsInfoRefer['goods_img'],
                    GoodsConstants::DETAIL_URL      => $detailUrl,
                    GoodsConstants::SHOP_PRICE      => ShopHelpers::getGoodsPriceString($goodsInfoRefer['shop_price']),
                    GoodsConstants::MARKET_PRICE    => ShopHelpers::getGoodsPriceString($marketPrice),
                    GoodsConstants::DISCOUNT        => ShopHelpers::getGoodsDiscount($marketPrice, $tskPrice),
                    GoodsConstants::STOCK_NUM       => $stockNum,
                    GoodsConstants::TSK_PRICE       => ShopHelpers::getGoodsPriceString($tskPrice),
                    GoodsConstants::TSK_TOTAL_NUM   => $tskTotalNum,
                    GoodsConstants::TSK_SALE_NUM    => $tskSaleNum,
                    GoodsConstants::TSK_LEFT_NUM    => $tskLeftNum
                ];
            }

            $result[KeyConstants::TSK_INFO] = [
                GoodsConstants::TSK_BEGIN_TIME  => $apiResultRefer['data']['tsk_info']['tsk_begin_time'],
                GoodsConstants::TSK_END_TIME    => $apiResultRefer['data']['tsk_info']['tsk_end_time'],
            ];
        }

        return $result;
    }
}
