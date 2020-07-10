<?php
namespace App\Services\EsSearch;

use Illuminate\Support\Arr;
use App\Base\GoodsConstants;
use App\Helpers\SiteHelpers;
use App\Helpers\ShopHelpers;
use App\Helpers\BeanHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\Site\Traits\ZafulBaseTransformerTrait;
use App\Services\Site\Zaful\ZafulBaseDataProvider;

/**
 * zaful 站点 ES 搜索结果转换
 *
 * @author tianhaishen
 */
class ZafulTransformer extends AbstractTransformer
{
    use ZafulBaseTransformerTrait;

    /** @var string 国家站编码，如 ZF/ZFFR */
    private $pipeline;

    /** @var ZafulBaseDataProvider 基础数据提供者 */
    private $baseDataProvider;

    /**
     * 构造函数
     *
     * @param string $siteCode 站点简码
     * @param string $pipeline 国家站编码
     * @param string $lang 语言简码
     */
    public function __construct($siteCode, $pipeline, $lang)
    {
        parent::__construct($siteCode, $lang);
        $this->pipeline = $pipeline;
        $this->baseDataProvider = BeanHelpers::getBaseDataProvider($this->siteCode);
    }

    /**
     * @inheritdoc
     */
    public function goodsInfo(array &$goodsInfoListRefer)
    {
        $isApp = SiteHelpers::isAppPlatform($this->siteCode);
        $isPc = SiteHelpers::isPcPlatform($this->siteCode);
        $gesGoodsInfoList = [];
        foreach ($goodsInfoListRefer as $goodsInfo) {
            if (!Arr::has($goodsInfo, ['skuId', 'goodsSn', 'urlTitle', 'goodsGrid'])) {
                continue;
            }

            $goodsId = $goodsInfo['skuId'];
            $domain = SiteHelpers::getZafulDomain($this->siteCode, $this->pipeline, $this->lang);
            $quickBuyUrl = $isPc
                ? $this->getPcGoodsQuickBuyUrl($domain, $goodsId)
                : '';

            // ES没有返回SKU对应的产品编号，使用SKU编号截取
            $productSn = $goodsInfo['productSn'] ?? substr($goodsInfo['goodsSn'], 0, -2);
            $detailUrl = $isApp
                ? $this->getAppGoodsDetailDeepLink($goodsId, $goodsInfo['urlTitle'])
                : $this->getGoodsDetailUrl($domain, $goodsInfo['urlTitle'], $productSn, $goodsId);

            $goodsImg = $this->getGoodsPreviewImgUrl($goodsInfo['goodsGrid']);

            $gesGoodsInfoList[] = [
                GoodsConstants::GOODS_ID        => $goodsId,
                GoodsConstants::GOODS_SN        => $goodsInfo['goodsSn'],
                GoodsConstants::GOODS_TITLE     => $goodsInfo['goodsTitle'] ?? '',
                GoodsConstants::GOODS_IMG       => $goodsImg,
                GoodsConstants::MARKET_PRICE    => ShopHelpers::getGoodsPriceString($goodsInfo['marketPrice'] ?? 0.0),
                GoodsConstants::SHOP_PRICE      => ShopHelpers::getGoodsPriceString($goodsInfo['displayPrice'] ?? 0.0),
                GoodsConstants::DISCOUNT        => $goodsInfo['discount'] ?? 0,
                'is_on_sale'    => 1,
                'goods_number'  => $goodsInfo['goodsNumber'] ?? 0,
                'url_quick'     => $quickBuyUrl,
                'url_title'     => $detailUrl,
            ];
        }

        return $gesGoodsInfoList;
    }

    /**
     * @inheritdoc
     */
    public function categoryInfo(array &$categoryInfoListRefer)
    {
        try {
            $allCategoryInfo = $this->baseDataProvider->getAllCategory($this->lang);
            $allCategoryInfo = array_column($allCategoryInfo, null, 'cat_id');
            return $this->transCategoryInfoRecursive($allCategoryInfo, $categoryInfoListRefer, 2);
        } catch (ApiRequestException $e) {
            ges_error_log(__CLASS__, '调用站点获取商品分类基础数据接口错误, 返回默认空数据。');
            return [];
        }
    }

    /**
     * 递归转换分类
     *
     * @param array $allCategoryInfoRefer
     * @param array $categoryInfoListRefer
     * @param int $level
     * @return array
     */
    private function transCategoryInfoRecursive(&$allCategoryInfoRefer, &$categoryInfoListRefer, $level)
    {
        $levelCategories = [];
        foreach ($categoryInfoListRefer as $categoryInfo) {
            $id = $categoryInfo['key'];
            if (!isset($allCategoryInfoRefer[$id]))
                continue;

            $_siteCatInfo = $allCategoryInfoRefer[$id];
            $_categoryInfo = [
                'item_id' => $_siteCatInfo['cat_id'],
                'item_title' => $_siteCatInfo['cat_name'],
                'sku_count' => $categoryInfo['count'],
            ];
            if (isset($categoryInfo['buckets']['catIdLevel'. $level])
                && !empty($categoryInfo['buckets']['catIdLevel'. $level])
            ) {
                $_categoryInfo['child_item'] = $this->transCategoryInfoRecursive(
                    $allCategoryInfoRefer, $categoryInfo['buckets']['catIdLevel'. $level], ($level + 1)
                );

                if (!empty($_categoryInfo['child_item'])) {
                    $levelCategories[] = $_categoryInfo;
                }
            } else {
                $levelCategories[] = $_categoryInfo;
            }

        }

        return $levelCategories;
    }
}
