<?php
namespace App\Services\NativePage;

use App\Base\KeyConstants;
use App\Base\AppConstants;
use App\Helpers\BeanHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\EsSearch\AbstractEsSearch;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;
use App\Services\EsSearch\SearchParamBuilder;
use App\Services\Site\AbstractSiteApiResultTransformer;

/**
 * 原生组件异步数据解析器
 *
 * @author TianHaisen
 */
class PageUiAsyncDataParser
{
    /** @var NativePageInfo 原生页面信息 */
    private $pageInfo;

    /** @var AbstractEsSearch ES搜索对象 */
    private $esSearch = null;

    /** @var SearchParamBuilder[] ES查询参数构建器  */
    private $esParamBuilder = [];

    /** @var AsyncApiCacheManager 组件接口缓存管理器  */
    private $apiCacheManager = null;

    /** @var AbstractSiteApiResultTransformer API接口返回处理器 */
    private $apiResultTransformer = null;

    /** @var AsyncDataSupplement 异步数据补充对象 */
    private $asyncDataSupplement = null;

    /** @var AppDataTransformer APP组件数据转换器  */
    private $appDataTransformer = null;

    /** @var WebDataTransformer Web组件数据转换器  */
    private $webDataTransformer = null;

    /** @var array HTTP传入参数 */
    private $httpParams;

    /** @var array 组件异步信息 */
    private $asyncInfo = [];

    /** @var array API请求信息 */
    private $apiRequestInfo = [];

    /** @var array API请求结果 */
    private $apiResultInfo = [];

    /** @var int API 编号 */
    private $apiCount = 100;

    /** @var array 引用空数组 */
    private $referEmptyArray = [];

    /** @var array bts ES分流结果参数 */
    private $btsResultInfo = null;

    /** @var int 解析模式 */
    private $parseModel;

    /** @var string 当前API版本 */
    private $apiVersion;

    /** @var bool 是否清除API结果缓存 */
    private $isClearApiResultCache = false;

    /**
     * 构造函数
     *
     * @param NativePageInfo $pageInfo 页面信息对象
     * @param array $httpParams HTTP传入参数
     */
    public function __construct(NativePageInfo $pageInfo, $httpParams = [])
    {
        $this->pageInfo = $pageInfo;
        $this->httpParams = $httpParams;
        $this->parseModel = NativePageInfo::PARSE_MODEL_DESIGN;
        $this->apiVersion = AppConstants::API_VERSION_V1;
    }

    /**
     * 设置解析模式
     *
     * @param int $model 解析模式NativePageInfo::PARSE_MODEL_*常量定义
     */
    public function setParseModel($model)
    {
        $this->parseModel = $model;
    }

    /**
     * 获取解析模式
     *
     * @return int
     */
    public function getParseModel()
    {
        return $this->parseModel;
    }

    /**
     * 设置当前API版本
     *
     * @param string $version API版本，常思考 AppConstants::API_VERSION_* 常量定义
     */
    public function setApiVersion($version)
    {
        $supportVersions = [
            AppConstants::API_VERSION_V1,
            AppConstants::API_VERSION_V2
        ];
        if (in_array($version, $supportVersions, true)) {
            $this->apiVersion = $version;
        }
    }

    /**
     * 获取当前API版本
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * 是否发布解析模式
     *
     * @return bool
     */
    public function isPublishParseModel()
    {
        return $this->getParseModel() === NativePageInfo::PARSE_MODEL_PUBLISH;
    }

    /**
     * 设置是否清除API接口结果缓存
     *
     * @param bool $isClearApiResultCache
     */
    public function setIsClearApiResultCache($isClearApiResultCache)
    {
        $this->isClearApiResultCache = $isClearApiResultCache;
    }

    /**
     * 是否设置了 ES搜索BTS分流信息
     *
     * @return bool
     */
    public function hasBtsResultInfo()
    {
        return $this->btsResultInfo !== null;
    }

    /**
     * 设置ES搜索BTS分流信息，只用设置一次
     *
     * @param array $btsInfo ES BTS分流信息
     */
    public function setBtsResultInfo($btsInfo)
    {
        if (null === $this->btsResultInfo && is_array($btsInfo) && !empty($btsInfo)) {
            $this->btsResultInfo = $btsInfo;
        }
    }

    /**
     * 获取ES搜索BTS分流信息
     *
     * @return array
     */
    public function getBtsResultInfo()
    {
        return empty($this->btsResultInfo) ? [] : $this->btsResultInfo;
    }

    /**
     * 获取组件接口缓存管理器
     * !!!注意： 在PageUiAsyncDataParser::parseUiComponentAsyncData调用后在获取，否则返回null
     *
     * @see PageUiAsyncDataParser::parseUiComponentAsyncData
     * @return AsyncApiCacheManager
     */
    public function getApiCacheManager()
    {
        return $this->apiCacheManager;
    }

    /**
     * 获取原生页面信息
     *
     * @return NativePageInfo
     */
    public function getPageInfo()
    {
        return $this->pageInfo;
    }

    /**
     * 获取异步数据补充对象
     *
     * @return AsyncDataSupplement
     */
    public function getAsyncDataSupplement()
    {
        if ($this->asyncDataSupplement === null) {
            $this->asyncDataSupplement = new AsyncDataSupplement($this);
        }
        return $this->asyncDataSupplement;
    }

    /**
     * 获取站点API接口返回处理器
     *
     * @return AbstractSiteApiResultTransformer
     */
    public function getSiteApiResultTransformer()
    {
        if ($this->apiResultTransformer === null) {
            $this->apiResultTransformer = BeanHelpers::getApiResultTransformer($this->pageInfo->getSiteCode());
        }
        return $this->apiResultTransformer;
    }

    /**
     * 获取Es搜索对象
     *
     * @return AbstractEsSearch
     */
    public function getEsSearch()
    {
        if ($this->esSearch === null) {
            $this->esSearch = BeanHelpers::getEsSearch(
                $this->pageInfo->getSiteCode(), $this->pageInfo->getLang(), $this->pageInfo->getPipeline()
            );
        }
        return $this->esSearch;
    }

    /**
     * 获取组件ES查询参数构建器
     *
     * @param int $componentId 组件ID
     * @return SearchParamBuilder|null
     */
    public function getEsParamBuilder($componentId)
    {
        if ($this->pageInfo->hasComponent($componentId)) {
            if (!isset($this->esParamBuilder[$componentId])) {
                $this->esParamBuilder[$componentId] = new SearchParamBuilder();
            }
            return $this->esParamBuilder[$componentId];
        }
        return null;
    }

    /**
     * 获取APP组件数据转换器
     *
     * @return AppDataTransformer
     */
    public function getAppDataTransformer()
    {
        if ($this->appDataTransformer === null) {
            $this->appDataTransformer = new AppDataTransformer($this);
        }
        return $this->appDataTransformer;
    }

    /**
     * 获取Web组件数据转换器
     *
     * @return WebDataTransformer
     */
    public function getWebDataTransformer()
    {
        if ($this->webDataTransformer === null) {
            $this->webDataTransformer = new WebDataTransformer($this);
        }
        return $this->webDataTransformer;
    }

    /**
     * 获取组件API请求信息
     *
     * @param int $componentId 组件ID
     * @return array
     */
    public function getComponentRequestApiInfo($componentId)
    {
        return $this->apiRequestInfo[$componentId] ?? [];
    }

    /**
     * 移除组件API请求信息
     *
     * @param int $componentId 组件ID
     */
    public function removeComponentRequestApiInfo($componentId)
    {
        if (isset($this->apiRequestInfo[$componentId])) {
            unset($this->apiRequestInfo[$componentId]);
        }
    }

    /**
     * 获取组件API请求返回
     *
     * @param int $componentId 组件ID
     * @return array
     */
    public function getComponentApiResult($componentId)
    {
        return $this->apiResultInfo[$componentId] ?? [];
    }

    /**
     * 获取组件异步数据, 返回引用
     * @param int $componentId 组件ID
     * @return array
     */
    public function &getComponentApiResultRefer($componentId)
    {
        if (isset($this->apiResultInfo[$componentId])) {
            return $this->apiResultInfo[$componentId];
        }
        return $this->referEmptyArray;
    }

    /**
     * 设置组件异步数据
     *
     * @param int $componentId 组件ID
     * @param array $data 异步数据
     */
    public function setComponentAsyncData($componentId, $data)
    {
        if ($this->pageInfo->hasComponent($componentId)) {
            if (isset($this->asyncInfo[$componentId])) {
                if (!empty($data)) {
                    $uiDataRefer = & $this->asyncInfo[$componentId];
                    foreach ($data as $key => $value) {
                        if (isset($uiDataRefer[$key])) {
                            continue;
                        }

                        $uiDataRefer[$key] = $value;
                    }
                    unset($uiDataRefer);
                }
            } else {
                $this->asyncInfo[$componentId] = $data;
            }

        }
    }

    /**
     * 获取组件异步数据
     *
     * @param int $componentId 组件ID
     * @return array|null
     */
    public function getComponentAsyncData($componentId)
    {
        return $this->asyncInfo[$componentId] ?? null;
    }

    /**
     * 获取组件异步数据, 返回引用
     * @param int $componentId 组件ID
     * @return array
     */
    public function &getComponentAsyncDataRefer($componentId)
    {
        if (isset($this->asyncInfo[$componentId])) {
            return $this->asyncInfo[$componentId];
        }
        return $this->referEmptyArray;
    }

    /**
     * 获取HTTP传入参数
     *
     * @param string $key 参数键名,空值返回所有
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    public function getHttpParam($key = null, $defaultValue = null)
    {
        if (empty($key)) {
            return $this->httpParams;
        }
        return $this->httpParams[$key] ?? $defaultValue;
    }

    /**
     * 解析页面UI组件异步数据
     *
     * @throws ApiRequestException
     */
    public function parseUiComponentAsyncData()
    {
        // 1. 解析组件配置里的异步数据，生成各系统API调用参数
        $this->pageInfo->componentEach(function ($componentId) {
            $this->buildAsyncApiInfoByComponent($componentId);
        });

        // 2. 组件关联处理
        $this->pageInfo->componentEach(function ($componentId) {
            $this->associationProcessingByComponent($componentId);
        });


        $this->apiCacheManager = new AsyncApiCacheManager($this, $this->apiRequestInfo);
        $apiRequest = new AsyncApiRequest($this, $this->apiCacheManager, $this->apiRequestInfo);
        if ($this->isClearApiResultCache) {
            $this->apiCacheManager->clearAllComponentApiResultCache();
        }
        // 3. 并发调用多个接口
        $apiRequest->requestAllApi();
        if ($apiRequest->hasFailed()) {
            throw new ApiRequestException('请求组件数据API失败');
        }
        $this->apiResultInfo = $apiRequest->getApiResultInfo();
        unset($apiRequest);

        // 4. 组装接口返回数据
        $this->pageInfo->componentEach(function ($componentId) {
            $this->transformApiResultByComponent($componentId);
        });

        // 5. 补充商品SKU价格
        $this->getAsyncDataSupplement()->requestGoodsSkuPriceApi();

        // 6. 补充数据
        $this->pageInfo->componentEach(function ($componentId) {
            $this->supplementaryAsyncDataByComponent($componentId);
        });
    }

    /**
     * 对组件异步数据进行兜底时，数据处理
     */
    public function fallbackAsyncInfo()
    {
        if (empty($this->asyncInfo)) {
            return;
        }

        foreach ($this->asyncInfo as &$componentAsyncInfoRefer) {
            if (!isset($componentAsyncInfoRefer[AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO])) {
                continue;
            }

            foreach ($componentAsyncInfoRefer[AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO] as &$skuInfoRefer) {
                if (isset($skuInfoRefer[KeyConstants::PAGINATION])) {
                    $skuInfoRefer[KeyConstants::PAGINATION][KeyConstants::TOTAL_COUNT] = $skuInfoRefer[KeyConstants::PAGINATION][KeyConstants::PAGE_SIZE];
                }
            }
        }
    }

    /**
     * 获取页面组件异步数据
     *
     * @return array
     */
    public function getAsyncInfo()
    {
        if (GES_ENABLE_TRACK_LOG) {
            return [
                'ui_info' => $this->pageInfo->toArray(),
                'request_info' => $this->apiRequestInfo,
                'result_into' => $this->apiResultInfo,
                'async_info' => $this->asyncInfo
            ];
        }
        return $this->asyncInfo;
    }

    /**
     * 根据组件模板生成 API 请求信息
     *
     * @param int $componentId 组件ID
     */
    private function buildAsyncApiInfoByComponent($componentId)
    {
        //获取单个组件模板初始化对象
        $tplParser = $this->getUiTplAsyncDataParser($componentId);
        if ($tplParser) {
            $apiInfoList = $tplParser->buildApiRequestInfo();
            if (!empty($apiInfoList) && is_array($apiInfoList)) {
                foreach ($apiInfoList as $apiInfo) {
                    $this->apiRequestInfo[$componentId][$this->getApiId()] = $apiInfo;
                }
            }
        }
    }

    /**
     * 根据组件模板关联处理
     *
     * @param int $componentId 组件ID
     */
    private function associationProcessingByComponent($componentId)
    {
        $tplParser = $this->getUiTplAsyncDataParser($componentId);
        if ($tplParser) {
            $tplParser->associationProcessing();
        }

    }

    /**
     * 根据组件模板转换API结果
     *
     * @param int $componentId 组件ID
     */
    private function transformApiResultByComponent($componentId)
    {
        $tplParser = $this->getUiTplAsyncDataParser($componentId);
        if ($tplParser) {
            $tplParser->transformApiResult();
        }
    }

    /**
     * 补充完整异步数据
     *
     * @param int $componentId 组件ID
     */
    private function supplementaryAsyncDataByComponent($componentId)
    {
        $tplParser = $this->getUiTplAsyncDataParser($componentId);
        if ($tplParser) {
            $tplParser->supplementaryAsyncData();
        }
    }

    /**
     * 获取组件模板解析器
     *
     * @param int $componentId 组件ID
     * @return AbstractUiTplAsyncApiParser
     */
    public function getUiTplAsyncDataParser($componentId)
    {
        return $this->pageInfo->getUiTplAsyncDataParser($this, $componentId);
    }

    /**
     * 获取API编号
     *
     * @return string
     */
    private function getApiId()
    {
        return $this->apiCount++;
    }
}
