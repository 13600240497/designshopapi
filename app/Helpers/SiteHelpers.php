<?php
namespace App\Helpers;

use Illuminate\Support\Str;
use App\Base\AppConstants;

/**
 * 站点相关函数
 *
 * @author TianHaisen
 */
class SiteHelpers
{
    /** @var string 站点简码分隔符 */
    const SITE_CODE_SEPARATOR = '-';

    /**
     * 获取zaful站点域名
     *
     * @param string $siteCode 站点简码，如 zf-wap/zf-app
     * @param string $pipeline 国家站编码，如 ZF/ZFFR
     * @param string $lang 语言简码，如 en/fr
     * @return string
     */
    public static function getZafulDomain($siteCode, $pipeline, $lang)
    {
        $host = config(sprintf('sites.%s.domain.%s', $siteCode, $pipeline));
        if (AppHelpers::isTestEnv()) {
            if (self::isPcPlatform($siteCode)) {
                $host = str_replace('.wap-', '.pc-', $host);
            }
            $host = str_replace('https://', 'http://', $host);
        }
        return $host;
    }

    /**
     * 分隔站点简码
     *
     * @param string $siteCode 站点简码
     *
     * @return array [zf(站点简码)， pc(平台简码)]
     */
    public static function splitSiteCode($siteCode)
    {
        return explode(self::SITE_CODE_SEPARATOR, $siteCode, 2);
    }

    /**
     * 是否为pc平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isPcPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_PC)) {
            return true;
        }

        return false;
    }

    /**
     * 是否为wap平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isWapPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_WAP)) {
            return true;
        }

        return false;
    }

    /**
     * 是否为app平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isAppPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_APP)) {
            return true;
        }

        return false;
    }

    /**
     * 是否为Android平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isAndroidPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_ANDROID)) {
            return true;
        }

        return false;
    }

    /**
     * 是否为IOS平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isIosPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_IOS)) {
            return true;
        }

        return false;
    }

    /**
     * 是否为Web平台
     *
     * @param string $siteCode 站点简码
     *
     * @return boolean true是，false 否
     */
    public static function isWebPlatform($siteCode)
    {
        if (Str::endsWith($siteCode, self::SITE_CODE_SEPARATOR . AppConstants::PLATFORM_CODE_WEB)) {
            return true;
        }

        return false;
    }
}
