<?php
namespace App\Services\Site\Traits;

/**
 * Zaful 站点基础数据转换函数
 *
 * @author tianhaishen
 */
trait ZafulBaseTransformerTrait
{
    /** @var string APP商品详情页面DeepLink链接格式 */
    private static $goodsDetailDeepLinkFormat = 'zaful://action?actiontype=3&url=%s&name=%s&source=deeplink';

    /** @var string 品快速购买链接式 */
    private static $goodsQuickBuyUrlFormat = '%s/m-goods_fast-a-info.htm?goods_id=%s&ge_newindex=1';

    /** @var string 商品详情页URL */
    private static $goodsDetailUrlFormat = '%s/%s-puid_%s.html?kuid=%d';

    /**
     * 获取APP商品详情页面DeepLink链接
     *
     * @param int $goodsId 商品ID
     * @param string $urlTitle 商品slug
     * @return string
     */
    protected function getAppGoodsDetailDeepLink($goodsId, $urlTitle)
    {
        return sprintf(self::$goodsDetailDeepLinkFormat, $goodsId, $urlTitle);
    }

    /**
     * 获取商品详情页URL地址
     *
     * @param string $domain 域名
     * @param string $urlTitle 商品slug
     * @param string $productSn 产品编号
     * @param int $goodsId 商品ID
     * @return string
     */
    protected function getGoodsDetailUrl($domain, $urlTitle, $productSn, $goodsId)
    {
        return sprintf(self::$goodsDetailUrlFormat, $domain, $urlTitle, $productSn, $goodsId);
    }

    /**
     * 获取PC端商品快速购买链接
     *
     * @param string $domain
     * @param int $goodsId
     * @return string
     */
    protected function getPcGoodsQuickBuyUrl($domain, $goodsId)
    {
        return sprintf(self::$goodsQuickBuyUrlFormat, $domain, $goodsId);
    }

    /**
     * 获取商品预览图片的完整URL
     *
     * @param string $uri 图片uri, 如： zaful/pdm-product-pic/Clothing/2018/06/05/grid-img/1560274086997671593.jpg
     * @return string
     */
    protected function getGoodsPreviewImgUrl($uri)
    {
        return 'https://gloimg.zafcdn.com/'. ltrim($uri, '/');
    }
}
