<?php
/**
 * 专题活动Rediskey相关管理
 */

namespace App\Gadgets\Rdkey\BussKey;


class PromotionKey
{
    // APP原生页Json文件数据缓存
    const NATIVE_APP_JSON_CACHE_KEY = FULL_DOMAIN . '::geshop::native::app::json';

    // WAP原生页Json文件数据缓存
    const NATIVE_WAP_JSON_CACHE_KEY = FULL_DOMAIN . '::geshop::native::wap::json';

    //环境开发者，用来隔离开类似“预发布”和“正式”这种公用数据库和域名的环境
    public $developer;

    public function __construct()
    {
        $this->developer = !empty(config('app.env')) ? config('app.env') : getenv('APP_ENV');
    }

    /**
     * 获取原生APP专题活动页组件数据
     *
     * @param string $siteCode
     *
     * @return string
     */
    public function getNativeAppJsonDataKey(string $siteCode)
    {
        return $this->developer . '::' . static::NATIVE_APP_JSON_CACHE_KEY . '::' . $siteCode;
    }

    /**
     * 获取原生Wap专题活动页组件数据
     *
     * @param string $siteCode
     *
     * @return string
     */
    public function getNativeWapJsonDataKey(string $siteCode)
    {
        return $this->developer . '::' . static::NATIVE_WAP_JSON_CACHE_KEY . '::' . $siteCode;
    }
}
