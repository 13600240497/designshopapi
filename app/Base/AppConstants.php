<?php
namespace App\Base;

/**
 * 公共常量定义
 *
 * @author TianHaisen
 */
interface AppConstants
{
    //------------ SKU来源 ----------------
    /** @var int SKU来源 - 手动输入 */
    const SKU_FROM_INPUT = 1;

    /** @var int SKU来源 - 商品运营平台 */
    const SKU_FROM_SOP = 2;

    /** @var int SKU来源 - 价格体系系统 */
    const SKU_FROM_PRICE_SYS = 3;

    //------------ API版本号 ----------------
    /** @var string API版本号 - v1 */
    const API_VERSION_V1 = 'v1';

    /** @var string API版本号 - v2 */
    const API_VERSION_V2 = 'v2';

    //------------ 网站简码 ----------------
    /** @var string RG(RoseGal)站点组简码 */
    const WEBSITE_CODE_RG = 'rg';

    /** @var string ZF(zaful)站点组简码 */
    const WEBSITE_CODE_ZF = 'zf';

    /** @var string DL(dresslily)站点组简码 */
    const WEBSITE_CODE_DL = 'dl';

    //------------ 端口简码 ----------------
    /** @var string 端口简码 - 桌面电脑 */
    const PLATFORM_CODE_PC = 'pc';

    /** @var string 端口简码 - 手机Wap */
    const PLATFORM_CODE_WAP = 'wap';

    /** @var string 端口简码 - 手机APP */
    const PLATFORM_CODE_APP = 'app';

    /** @var string 端口简码 - 苹果系统 */
    const PLATFORM_CODE_IOS = 'ios';

    /** @var string 端口简码 -  安卓 */
    const PLATFORM_CODE_ANDROID = 'android';

    /** @var string 端口简码 - Web自适应(适配PC、平板、手机) */
    const PLATFORM_CODE_WEB = 'web';

    //------------ 时间单位 ----------------
    /** @var int 时间单位一小时秒数 */
    const TIME_UNIT_HOUR = 60 * 60;

    /** @var int 时间单位一天秒数  */
    const TIME_UNIT_DAY = self::TIME_UNIT_HOUR * 24;

}
