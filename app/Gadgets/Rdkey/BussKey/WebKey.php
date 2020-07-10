<?php
namespace App\Gadgets\Rdkey\BussKey;

use App\Helpers\AppHelpers;

/**
 * 提供非原生页面接口相关缓存
 */
class WebKey
{
    /** @var string 环境开发者，用来隔离开类似“预发布”和“正式”这种公用数据库和域名的环境 */
    private $env;

    public function __construct()
    {
        $this->env = AppHelpers::getEnv();
    }

    /**
     * 实时价格 接口数据
     *
     * @param string $siteCode 站点简码，如: zf-wap
     * @param int $pageId 页面ID
     * @param string $lang 语言简码，如： en/fr
     * @return string
     */
    public function getRealTimePriceKey($siteCode, $pageId, $lang)
    {
        return $this->getKey(sprintf(':geshopApi:web:realTimePrice:%s:%d:%s', $siteCode, $pageId, $lang));
    }

    /**
     * 获取完整键名
     *
     * @param string $key 键名
     * @return string
     */
    private function getKey($key)
    {
        return $this->env . $key;
    }
}
