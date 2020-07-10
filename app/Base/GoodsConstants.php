<?php
namespace App\Base;

/**
 * 商品字段常量定义，API层接口返回的商品信息统一字段名称
 * !!! 注意： 个别以前老接口还是使用没有统一,但是新增接口要使用这里的统一字段
 *
 * 没有统一的接口列表：
 * @see \App\Services\EsSearch\ZafulTransformer::goodsInfo
 *
 * @author TianHaisen
 */
interface GoodsConstants
{
    /** @var string 商品字段 - 商品ID */
    const GOODS_ID = 'goods_id';

    /** @var string 商品字段 - 商品编码 */
    const GOODS_SN = 'goods_sn';

    /** @var string 商品字段 - 商品标题 */
    const GOODS_TITLE = 'goods_title';

    /** @var string 商品字段 - 商品图片完整URL */
    const GOODS_IMG = 'goods_img';

    /** @var string 商品字段 - 商品详情页URL(APP端为DeepLink) */
    const DETAIL_URL = 'detail_url';

    /** @var string 商品字段 - 快速购买链接 */
    const QUICK_BUY_URL = 'quick_buy_url';

    /** @var string 商品字段 - 正常售价(店铺价) */
    const SHOP_PRICE = 'shop_price';

    /** @var string 商品字段 - APP专享价 */
    const APP_PRICE = 'app_price';

    /** @var string 商品字段 - 市场价 */
    const MARKET_PRICE = 'market_price';

    /** @var string 商品字段 - (xx价/xx价) * 100 */
    const DISCOUNT = 'discount';

    /** @var string 商品字段 - 可销售库存 */
    const STOCK_NUM = 'stock_num';

    /** @var string 商品字段 - 营销信息数据 */
    const PROMOTIONS = 'promotions';



    /** @var string 商品字段 - 秒杀价格 */
    const TSK_PRICE = 'tsk_price';

    /** @var string 商品字段 - 秒杀总数量 */
    const TSK_TOTAL_NUM = 'tsk_total_num';

    /** @var string 商品字段 - 已秒杀数量 */
    const TSK_SALE_NUM = 'tsk_sale_num';

    /** @var string 商品字段 - 剩余秒杀数量 */
    const TSK_LEFT_NUM = 'tsk_left_num';

    /** @var string 商品字段 - 秒杀开始时间戳 */
    const TSK_BEGIN_TIME = 'tsk_begin_time';

    /** @var string 商品字段 - 秒杀结束时间戳 */
    const TSK_END_TIME = 'tsk_end_time';

}
