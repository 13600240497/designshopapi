<?php
namespace App\Gadgets\Rdkey\BussKey;

use App\Helpers\AppHelpers;

/**
 * API接口相关缓存
 */
class ApiKey
{
    /** @var string 环境开发者，用来隔离开类似“预发布”和“正式”这种公用数据库和域名的环境 */
    private $env;

    public function __construct()
    {
        $this->env = AppHelpers::getEnv();
    }

    /**
     * 获取站点分类
     *
     * @param string $websiteCode 网站简码，如： zf/rg
     * @param string $lang 语言简码，如： en/fr
     * @return string
     */
    public function getSiteCategory($websiteCode, $lang)
    {
        return $this->getKey(sprintf(':geshopApi:site:%s:category:%s', $websiteCode, $lang));
    }

    /**
     * 获取站点属性
     *
     * @param string $websiteCode 网站简码，如： zf/rg
     * @param string $lang 语言简码，如： en/fr
     * @param int $id 属性ID
     * @return string
     */
    public function getSiteAttr($websiteCode, $lang, $id)
    {
        return $this->getKey(sprintf(':geshopApi:site:%s:attr:%s:%d', $websiteCode, $lang, $id));
    }

    /**
     * goods_getDetail 接口数据
     *
     * @param string $siteCode 站点简码，如: zf-wap
     * @param int $pageId 页面ID
     * @param string $lang 语言简码，如： en/fr
     * @param int $componentId 组件ID
     * @return string
     */
    public function getGoodsGetDetail($siteCode, $pageId, $lang, $componentId)
    {
        return $this->getKey(sprintf(':geshopApi:api:getDetail:%s:%d:%s:%d', $siteCode, $pageId, $lang, $componentId));
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
