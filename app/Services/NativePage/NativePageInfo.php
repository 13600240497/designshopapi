<?php
namespace App\Services\NativePage;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\URL;
use App\Helpers\SiteHelpers;
use App\Helpers\AppHelpers;
use App\Helpers\S3Helpers;
use App\Helpers\BeanHelpers;
use App\Helpers\ContainerHelpers;
use App\Combines\NativePageCombines;
use App\Services\Site\AbstractS3FileManager;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;

/**
 * 原生组件页面
 *
 * @author TianHaisen
 */
class NativePageInfo
{
    /** @var AbstractUiTplAsyncApiParser[] 组件模板解析器实例 */
    private static $uiTplAsyncApiInstance = [];

    /** @var int 解析模式 - 装修模式 */
    const PARSE_MODEL_DESIGN = 1;

    /** @var int 解析模式 - 预览模式 */
    const PARSE_MODEL_PREVIEW = 2;

    /** @var int 解析模式 - 发布模式 */
    const PARSE_MODEL_PUBLISH = 3;


    /** @var string 组件字段 - SKU数据字段 */
    const UI_KEY_SKU_DATA = 'sku_data';

    /** @var string 组件字段 - 设置数据字段 */
    const UI_KEY_SETTING_DATA = 'setting_data';

    /** @var string 组件字段 - 样式数据 */
    const UI_KEY_STYLE_DATA = 'style_data';

    /** @var string 组件字段 - 组件ID */
    const UI_KEY_ID = 'component_id';

    /** @var string 组件字段 - 组件ID */
    const UI_KEY_COMPONENT_KEY = 'component_key';

    /** @var array 根据 userGroup 取对应的范围值 */
    const USER_GROUP_MAP = [[0, 1], [0, 2]];

    /** @var string 网站简码 */
    private $siteCode;

    /** @var int 页面ID */
    private $pageId;

    /** @var string 国家站编码 */
    private $pipeline;

    /** @var string 语言简码 */
    private $lang;

    /** @var string 网站简码, 如: zf */
    protected $websiteCode;

    /** @var string 平台简码, 如: wap */
    protected $platformCode;

    /** @var array 过滤组件ID列表 */
    private $filterComponentIds = [];

    /** @var int 新老用户 */
    private $userGroup = -1;

    /** @var array 页面所有组件数据 */
    private $allUiData = null;

    /** @var array 引用空数组 */
    private $referEmptyArray = [];

    /**
     * 构造函数
     *
     * @param string $siteCode 网站简码
     * @param int $pageId 页面ID
     * @param string $pipeline 国家站编码
     * @param string $lang 语言简码
     * @param array $pageUiData 页面组件数据
     */
    public function __construct(string $siteCode, int $pageId, string $pipeline, string $lang, $pageUiData = [])
    {
        $this->siteCode = $siteCode;
        $this->pageId = $pageId;
        $this->pipeline = $pipeline;
        $this->lang = $lang;
        list($this->websiteCode, $this->platformCode) = SiteHelpers::splitSiteCode($siteCode);

        //初始化页面组件数据[如果是单个组件接口,通过page_id无法初始化组件数据
        //如果是装修页数据接口,通过page_id初始化会有默认语言替换当前语言的组件数据逻辑,以上两种情况需要传入组件数据数组]
        if (empty($pageUiData)) {
            $this->loadAllUiData();
        } else {
            $this->allUiData = array_column($pageUiData, null, self::UI_KEY_ID);
        }

        if (empty($this->allUiData)) {
            $fullUrl = URL::full();
            $postParams = request()->post();
            $logFormat = '没有找到活动页面[%s:%s:%s]的组件数据,来自请求[%s - %s]';
            ges_warning_log(__CLASS__, $logFormat, $this->pageId, $this->pipeline, $this->lang, $fullUrl, $postParams);
        }
    }

    /**
     * 调试使用
     * @return array
     */
    public function toArray()
    {
        return $this->allUiData;
    }

    /**
     * 设置过滤组件ID列表
     *
     * @param array $ids 组件ID列表
     */
    public function setFilterComponentIds(array $ids)
    {
        if (is_array($ids) && !empty($ids)) {
            $this->filterComponentIds = array_unique($ids);
        }
    }

    /**
     * 设置新老用户类型
     *
     * @param int $type 用户类型（0 新用户, 1 老用）
     */
    public function setUserGroup($type)
    {
        $group = (int)$type;
        if (in_array($group, [0, 1])) {
            $this->userGroup = $group;
        }
    }

    /**
     * 获取网站简码
     * @return string
     */
    public function getSiteCode(): string
    {
        return $this->siteCode;
    }

    /**
     * 获取页面ID
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * 获取国家站编码
     * @return string
     */
    public function getPipeline(): string
    {
        return $this->pipeline;
    }

    /**
     * 获取语言简码
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * 网站简码, 如: zf
     * @return string
     */
    public function getWebsiteCode(): string
    {
        return $this->websiteCode;
    }

    /**
     * 平台简码, 如: wap
     * @return string
     */
    public function getPlatformCode(): string
    {
        return $this->platformCode;
    }

    /**
     * 判断某个组件是否存在
     *
     * @param int $componentId 组件ID
     * @return bool
     */
    public function hasComponent($componentId)
    {
        return isset($this->allUiData[$componentId]);
    }

    /**
     * 获取组件模板解析器
     *
     * @param PageUiAsyncDataParser $pageParser 页面异步数据解析器
     * @param int $componentId 组件ID
     * @return AbstractUiTplAsyncApiParser
     */
    public function getUiTplAsyncDataParser(PageUiAsyncDataParser $pageParser, $componentId)
    {
        if (!array_key_exists($componentId, static::$uiTplAsyncApiInstance)) {
            $parserInstance = null;
            if ($this->hasComponent($componentId)) {
                $uiKey = $this->allUiData[$componentId][self::UI_KEY_COMPONENT_KEY];
                $tplKey = $this->allUiData[$componentId]['tpl_name'];
                $classNamePrefix = $uiKey . '_' . $tplKey;
                $fullClassName = sprintf("%s\UiTplParser\%sParser", __NAMESPACE__, $classNamePrefix);
                if (class_exists($fullClassName)) {
                    /** @var AbstractUiTplAsyncApiParser $parserInstance */
                    $parserInstance = new $fullClassName();
                    $parserInstance->init($pageParser, $componentId);

                }
            }
            static::$uiTplAsyncApiInstance[$componentId] = $parserInstance;
        }

        return static::$uiTplAsyncApiInstance[$componentId];
    }

    /**
     * 组件循环执行回调，使用显示过滤和新老用户过滤
     *
     * @param Closure $callback 回调函数,参数数据ID
     */
    public function componentEach(Closure $callback)
    {
        if (empty($this->allUiData)) {
            return;
        }

        $isEmptyFilterIds = empty($this->filterComponentIds);
        $isNotSetUserGroup = $this->userGroup === -1;
        foreach ($this->allUiData as &$uiDataRefer) {
            $componentId = $uiDataRefer[self::UI_KEY_ID];
            // 指定显示组件过滤
            $isFilterId = $isEmptyFilterIds || in_array($componentId, $this->filterComponentIds);

            // 新老用户过滤
            $isUserGroup = $isNotSetUserGroup || !isset($uiDataRefer[self::UI_KEY_SETTING_DATA]['userGroup'])
                || in_array($uiDataRefer[self::UI_KEY_SETTING_DATA]['userGroup'], self::USER_GROUP_MAP[$this->userGroup]);

            if ($isFilterId && $isUserGroup) {
                $callback($componentId);
            }
        }
        unset($uiDataRefer);
    }

    /**
     * 获取页面组件数据, 返回引用
     * @param int $componentId 组件ID
     * @return array
     */
    public function &getComponentDataRefer($componentId)
    {
        if (isset($this->allUiData[$componentId])) {
            return $this->allUiData[$componentId];
        }
        return $this->referEmptyArray;
    }

    /**
     * 获取组件指定键名的设置数据
     *
     * @param int $componentId 组件ID
     * @param string $settingKey 属性键名称
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    public function getComponentSettingValue($componentId, $settingKey, $defaultValue = null)
    {
        return $this->allUiData[$componentId][self::UI_KEY_SETTING_DATA][$settingKey] ?? $defaultValue;
    }

    /**
     * 获取组件SKU配置数据
     *
     * @param int $componentId 组件ID
     * @return array|null
     */
    public function getComponentSkuData($componentId)
    {
        if ($this->hasSkuData($componentId)) {
            return $this->allUiData[$componentId][self::UI_KEY_SKU_DATA];
        }
        return null;
    }

    /**
     * 是否包括sku_data字段数据
     *
     * @param int $componentId 组件ID
     * @return bool
     */
    public function hasSkuData($componentId)
    {
        if (isset($this->allUiData[$componentId][self::UI_KEY_SKU_DATA])
            && !empty($this->allUiData[$componentId][self::UI_KEY_SKU_DATA])
        ) {
            return true;
        }
        return false;
    }

    /**
     * 加载页面所有组件数据
     */
    private function loadAllUiData()
    {
        $cache = ContainerHelpers::getPredisResolveCache();
        $defaultLang = config(sprintf('site.%s.pipelineDefaultLang.%s', $this->websiteCode, $this->pipeline));

        $promotionKey = ContainerHelpers::getRedisKey()->rdkPromotion;
        $cacheKey = SiteHelpers::isAppPlatform($this->siteCode)
            ? $promotionKey->getNativeAppJsonDataKey($this->siteCode)
            : $promotionKey->getNativeWapJsonDataKey($this->siteCode);
        $fieldKey = sprintf('%d::%s::%s', $this->pageId, $this->pipeline, $this->lang);

        $componentData = [];
        // 取redis缓存数据
        try {
            $_fieldKey = $fieldKey;
            if (!$cache->hexists($cacheKey, $_fieldKey) && ($this->lang !== $defaultLang)) {
                //当前语言没有页面组件缓存数据就替换成当前渠道默认语言的页面组件数据
                $_fieldKey = sprintf('%d::%s::%s', $this->pageId, $this->pipeline, $defaultLang);
            }
            $dataString = $cache->hget($cacheKey, $_fieldKey);
            if (!empty($dataString)) {
                $componentData = $this->getDataFromJsonBody(AppHelpers::uncompress($dataString));
                if (!empty($componentData) && is_array($componentData)) {
                    $logFormat = '活动页面[%s:%s:%s]从Redis[%s => %s]里获取数据： %s';
                    ges_track_log(__CLASS__, $logFormat,
                        $this->pageId, $this->pipeline, $this->lang, $cacheKey, $_fieldKey, $componentData
                    );
                }
            }
            unset($_fieldKey);
        } catch (\Exception $e) {
            report($e);
            $componentData = [];
        }

        // 取S3数据
        if (empty($componentData)) {
            try {
                $componentData = $this->tryLoadComponentDataFromS3($cache, $cacheKey, $fieldKey, $defaultLang);
            } catch (\Exception $e) {
                report($e);
                $componentData = [];
            }
        }

        // 取数据库
        if (empty($componentData)) {
            try {
                $componentData = $this->tryLoadComponentDataFromDatabase($cache, $cacheKey, $fieldKey, $defaultLang);
            } catch (\Exception $e) {
                report($e);
                $componentData = [];
            }
        }

        if (is_string($componentData) && !empty($componentData)) {
            $componentData = $this->getDataFromJsonBody($componentData);
        }
        $this->allUiData = empty($componentData) ? [] : array_column($componentData, null, self::UI_KEY_ID);

        // 检查语言站点是否支持
        $this->checkSiteSupportLang($defaultLang);
    }

    /**
     * 检查语言站点是否支持，不支持使用默认语言
     * @param string $defaultLang
     */
    protected function checkSiteSupportLang($defaultLang)
    {
        if ($this->pipeline === 'ZFSG' && $this->lang === 'id') {
            $this->lang = $defaultLang;
        }
    }

    /**
     * 尝试从数据库加载页面组件
     *
     * @param Connection $redis
     * @param string $cacheKey
     * @param string $fieldKey
     * @param string $defaultLang
     * @return array
     */
    private function tryLoadComponentDataFromDatabase($redis, $cacheKey, $fieldKey, $defaultLang)
    {
        /** @var NativePageCombines $nativePageCombines */
        $nativePageCombines = app(NativePageCombines::class);
        list($useLangCode, $componentData) = $nativePageCombines->getUseComponentData($this->pageId, $this->lang, $defaultLang);
        if (!empty($componentData) && is_array($componentData)) {
            $redis->hset($cacheKey, $fieldKey, AppHelpers::compress(AppHelpers::jsonEncode($componentData)));
            $logFormat = '活动页面[%s:%s:%s]从数据库(%s)加载数据: %s';
            ges_track_log(__CLASS__, $logFormat, $this->pageId, $this->pipeline, $this->lang, $useLangCode, $componentData);
            return $componentData;
        }

        return [];
    }

    /**
     * 尝试从S3生成的json文件加载页面组件
     *
     * @param Connection $redis
     * @param string $cacheKey
     * @param string $fieldKey
     * @param string $defaultLang
     * @return array
     */
    private function tryLoadComponentDataFromS3($redis, $cacheKey, $fieldKey, $defaultLang)
    {
        $s3FileManager = BeanHelpers::getS3FileManager($this->siteCode, $this->lang, $this->pipeline);
        $filename = S3Helpers::getNativePageUiDataJsonFile($this->pageId);

        $s3JsonUrl = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonBody = $this->getS3FileBody($s3FileManager, $s3JsonUrl);
        if (empty($jsonBody) && ($this->lang !== $defaultLang)) {
            $s3FileManager = BeanHelpers::getS3FileManager($this->siteCode, $defaultLang, $this->pipeline);
            $s3JsonUrl = $s3FileManager->getJsonDataFileUrl($filename);
            $jsonBody = $this->getS3FileBody($s3FileManager, $s3JsonUrl);
        }
        unset($s3FileManager);

        if (!empty($jsonBody)) {
            $data = $this->getDataFromJsonBody($jsonBody);
            if (!empty($data) && is_array($data)) {
                $redis->hset($cacheKey, $fieldKey, AppHelpers::compress($jsonBody));
                $logFormat = '活动页面[%s:%s:%s]从S3[%s]加载数据';
                ges_track_log(__CLASS__, $logFormat, $this->pageId, $this->pipeline, $this->lang, $s3JsonUrl);
                return $data;
            }
        }

        return [];
    }

    /**
     * 获取S3文件内容
     *
     * @param AbstractS3FileManager $s3FileManager
     * @param string $s3Url
     * @return string
     */
    private function getS3FileBody($s3FileManager, $s3Url)
    {
        try {
            return $s3FileManager->getFileBody($s3Url);
        } catch (RequestException $e) {
            $logFormat = '活动页面[%s:%s:%s]尝试从S3文件[%s]加载数据失败: %s';
            ges_track_log(__CLASS__, $logFormat, $this->pageId, $this->pipeline, $this->lang, $s3Url, $e->getMessage());
            return '';
        }
    }

    /**
     * 换json内容转换为数组
     *
     * @param string $json json内容
     * @return array
     */
    private function getDataFromJsonBody($json)
    {
        $data = (!empty($json) && is_string($json)) ? AppHelpers::jsonDecode($json, true) : [];
        return is_array($data) ? $data : [];
    }
}
