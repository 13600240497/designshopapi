<?php
namespace App\Services\NativePage;

use Illuminate\Redis\Connections\Connection;
use App\Base\ApiConstants;
use App\Base\AppConstants;
use App\Helpers\AppHelpers;
use App\Helpers\ContainerHelpers;
use App\Gadgets\Rdkey\BussKey\ApiKey;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 组件接口缓存管理器
 *
 * @author TianHaisen
 */
class AsyncApiCacheManager
{
    /** @var string 网站简码 */
    private $siteCode;

    /** @var int 页面ID */
    private $pageId;

    /** @var string 国家站编码 */
    private $pipeline;

    /** @var string 语言简码 */
    private $lang;

    /** @var PageUiAsyncDataParser 页面解析器 */
    private $pageParser;

    /** @var ApiKey API缓存key*/
    private $apiRedisKey;

    /** @var Connection Redis连接对象 */
    private $apiRedis;

    /** @var array 异步API信息列表 */
    private $asyncApiInfoList;

    /** @var array API接口配置 */
    private $asyncApiConfig = [];

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser 页面解析器
     * @param array $asyncApiInfoList 异步API信息列表
     */
    public function __construct(PageUiAsyncDataParser $pageParser, $asyncApiInfoList)
    {
        $this->pageParser = $pageParser;

        $pageInfo = $this->pageParser->getPageInfo();
        $this->siteCode = $pageInfo->getSiteCode();
        $this->pageId = $pageInfo->getPageId();
        $this->pipeline = $pageInfo->getPipeline();
        $this->lang = $pageInfo->getLang();

        $this->asyncApiInfoList = $asyncApiInfoList;
        $this->asyncApiConfig = AppHelpers::getAsyncApiConfig($this->siteCode);
        $this->apiRedisKey = ContainerHelpers::getRedisKey()->api;
        $this->apiRedis = ContainerHelpers::getPredisResolveCache();
    }

    /**
     * 切换到关联页面，现在WAP和APP装修数据是同步的
     * !!!注意：如果后期AP和APP装修数据不同步，不能使用这个方法
     *
     * @param string $siteCode
     * @param int $pageId
     */
    public function switchToRelatedPage($siteCode, $pageId)
    {
        if ($this->siteCode !== $siteCode && ((int)$this->pageId !== (int)$pageId)) {
            $this->siteCode = $siteCode;
            $this->pageId = $pageId;
            $this->asyncApiConfig = AppHelpers::getAsyncApiConfig($this->siteCode);
        }
    }

    /**
     * 清除所有组件API结果缓存数据
     */
    public function clearAllComponentApiResultCache()
    {
        foreach ($this->asyncApiInfoList as $componentId => $uiApiInfoList) {
            foreach ($uiApiInfoList as $apiId => $apiInfo) {
                // 删除缓存
                $this->delCache($componentId, $apiId, $apiInfo);
            }
        }
    }

    /**
     * 检查API是否支持返回缓存
     *
     * @param string $apiName API接口名称
     * @return bool
     */
    public function isSupportCache($apiName)
    {
        // 非发布解析模式不使用缓存
        if (!$this->pageParser->isPublishParseModel()) {
            return false;
        }

        $cacheApiNames = [
            ApiConstants::API_NAME_GET_DETAIL
        ];
        return in_array($apiName, $cacheApiNames, true);
    }

    /**
     * API接口数据缓存
     *
     * @param int $componentId 组件ID
     * @param array $apiInfo API请求信息
     * @param string $resultBody 返回结果
     */
    public function setCache($componentId, $apiInfo, $resultBody)
    {
        $apiName = $apiInfo[SkuDataParser::KEY_API_NAME];

        if (ApiConstants::API_NAME_GET_DETAIL === $apiName) { // 商品详情接口
            $cacheKey = $this->apiRedisKey->getGoodsGetDetail($this->siteCode, $this->pageId, $this->lang, $componentId);
            $seconds = AppConstants::TIME_UNIT_HOUR * 6;
            $this->apiRedis->setex($cacheKey, $seconds, AppHelpers::compress($resultBody));
            $logFormat = 'API接口[%s]，设置Redis缓存[%s]数据： %s';
            ges_track_log(__CLASS__, $logFormat, $apiName, $cacheKey, $resultBody);
        }
    }

    /**
     * 是否从缓存缓存获取数据
     *
     * @param int $componentId 组件ID
     * @param int $apiId 组件下的API请求ID
     * @param array $apiInfo API请求信息
     * @return string
     */
    public function getCache($componentId, $apiId, $apiInfo)
    {
        $apiName = $apiInfo[SkuDataParser::KEY_API_NAME];

        if (ApiConstants::API_NAME_GET_DETAIL === $apiName) { // 商品详情接口
            $cacheKey = $this->apiRedisKey->getGoodsGetDetail($this->siteCode, $this->pageId, $this->lang, $componentId);
            $goodsInfoJsonBody = $this->apiRedis->get($cacheKey);
            if (!empty($goodsInfoJsonBody)) {
                $goodsInfoJsonBody = AppHelpers::uncompress($goodsInfoJsonBody);
                $logFormat = 'API接口[%s]，从Redis缓存[%s]获取数据： %s';
                ges_track_log(__CLASS__, $logFormat, $apiName, $cacheKey, $goodsInfoJsonBody);
                return $goodsInfoJsonBody;
            }
        }

        return null;
    }

    /**
     * 删除API结果缓存数据
     *
     * @param int $componentId 组件ID
     * @param int $apiId 组件下的API请求ID
     * @param array $apiInfo API请求信息
     */
    public function delCache($componentId, $apiId, $apiInfo)
    {
        $apiName = $apiInfo[SkuDataParser::KEY_API_NAME];

        if (ApiConstants::API_NAME_GET_DETAIL === $apiName) { // 商品详情接口
            $cacheKey = $this->apiRedisKey->getGoodsGetDetail($this->siteCode, $this->pageId, $this->lang, $componentId);
            $number = $this->apiRedis->del([$cacheKey]);
            if ($number > 0) {
                $logFormat = 'API接口[%s]，删除Redis缓存[%s]数据';
                ges_track_log(__CLASS__, $logFormat, $apiName, $cacheKey);
            }
        }
    }
}
