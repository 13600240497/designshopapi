<?php
namespace App\Helpers;

use App\Base\AppConstants;

/**
 * 单例对象相关助手方法
 *
 * @author TianHaisen
 */
class BeanHelpers
{
    /** @var array 类型实例对象缓存 */
    private static $beanInstances = [];

    /**
     * 获取ES搜索实例
     *
     * @param string $siteCode 站点简码，如 zf-wap/zf-app
     * @param string $lang 语言简码，如 en/fr
     * @param string $pipeline zaful国家站编码，如 ZF/ZFFR
     * @return \App\Services\EsSearch\AbstractEsSearch
     */
    public static function getEsSearch($siteCode, $lang, $pipeline = '')
    {
        $key = sprintf('EsSearch_%s_%s_%', $siteCode, $lang, $pipeline);
        $classes = self::getAllClass(\App\Services\EsSearch\ZafulEsSearch::class);
        return self::getSiteInstance($key, $siteCode, $classes, [$siteCode, $pipeline, $lang]);
    }

    /**
     * 获取站点基础数据提供者对象
     *
     * @param string $siteCode 站点简码，如 zf-wap/zf-app
     * @return \App\Services\Site\IBaseDataProvider
     */
    public static function getBaseDataProvider($siteCode)
    {
        $key = sprintf('BaseDataProvider_%s', $siteCode);
        $classes = self::getAllClass(\App\Services\Site\Zaful\ZafulBaseDataProvider::class);
        return self::getSiteInstance($key, $siteCode, $classes, [$siteCode]);
    }

    /**
     * 获取站点API接口返回处理器
     *
     * @param string $siteCode 站点简码，如 zf-wap/zf-app
     * @return \App\Services\Site\AbstractSiteApiResultTransformer
     */
    public static function getApiResultTransformer($siteCode)
    {
        $key = sprintf('ApiResultTransformer_%s', $siteCode);
        $classes = self::getAllClass(\App\Services\Site\Zaful\ZafulApiResultTransformer::class);
        return self::getSiteInstance($key, $siteCode, $classes, [$siteCode]);
    }

    /**
     * 获取站点S3文件管理器
     *
     * @param string $siteCode 站点简码，如 zf-wap/zf-app
     * @param string $lang 语言简码，如 en/fr
     * @param string $pipeline zaful国家站编码，如 ZF/ZFFR
     * @return \App\Services\Site\AbstractS3FileManager
     */
    public static function getS3FileManager($siteCode, $lang, $pipeline = '')
    {
        $key = sprintf('S3FileManager_%s_%s_%', $siteCode, $lang, $pipeline);
        $classes = self::getAllClass(\App\Services\Site\Zaful\ZafulS3FileManager::class);
        return self::getSiteInstance($key, $siteCode, $classes, [$siteCode, $lang, $pipeline]);
    }

    private static function getAllClass($className)
    {
        return [
            AppConstants::WEBSITE_CODE_ZF => $className,
            AppConstants::WEBSITE_CODE_RG => $className,
            AppConstants::WEBSITE_CODE_DL => $className,
        ];
    }

    /**
     * 获取对应站点实例对象
     *
     * @param string $key 缓存键名称
     * @param string $siteCode 站点简码，如： zf-pc
     * @param array $classes 站点支持类名称
     * @param array $params 实例化对象参数
     * @return mixed
     */
    private static function getSiteInstance($key, $siteCode, $classes, $params = null)
    {
        if (!array_key_exists($key, self::$beanInstances)) {
            $instance = null;
            list($websiteCode,) = SiteHelpers::splitSiteCode($siteCode);

            if (isset($classes[$websiteCode])) {
                $class = $classes[$websiteCode];
                try {
                    $rc = new \ReflectionClass($class);
                    if (empty($params)) {
                        $instance = $rc->newInstance();
                    } else {
                        $instance = $rc->newInstanceArgs($params);
                    }
                } catch (\ReflectionException $e) {}
            }

            self::$beanInstances[$key] = $instance;
        }

        return self::$beanInstances[$key];
    }
}
