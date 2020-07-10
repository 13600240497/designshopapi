<?php
namespace App\Helpers;

/**
 * zaful S3文件管理测试
 *
 * @author tianhaishen
 */
class S3Helpers
{
    /**
     * 获取自动刷新组件，异步数据json文件名称
     *
     * @param int $pageId 活动页面ID
     * @return string
     */
    public static function getUiAutoRefreshJsonFile($pageId)
    {
        return sprintf('async-data-%d.json', $pageId);
    }

    /**
     * 获取原生活动页面组件数据json文件名称
     *
     * @param int $pageId 活动页面ID
     * @return string
     */
    public static function getNativePageUiDataJsonFile($pageId)
    {
        return sprintf('api-async-data-%s.json', $pageId);
    }

    /**
     * 获取原生活动页面App端兜底数据json文件名称
     *
     * @param int $pageId 活动页面ID
     * @return string
     */
    public static function getNativePageAppFallbackDataJsonFile($pageId)
    {
        return sprintf('app-component-data-%d.json', $pageId);
    }

    /**
     * 获取原生活动页面M端兜底数据json文件名称
     *
     * @param int $pageId 活动页面ID
     * @return string
     */
    public static function getNativePageWapFallbackDataJsonFile($pageId)
    {
        return sprintf('wap-component-data-%d.json', $pageId);
    }

}

