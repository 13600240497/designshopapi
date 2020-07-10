<?php
namespace App\Services\NativePage;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use App\Base\ApiConstants;
use App\Helpers\AppHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\AbstractGuzzleHttp;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;

/**
 * 站点异步接口并发请求
 *
 * @author TianHaisen
 */
class AsyncApiRequest extends AbstractGuzzleHttp
{
    /** @var int 最大重试次数 */
    private $maxRetries = 0;

    /** @var PageUiAsyncDataParser 页面解析器 */
    private $pageParser;

    /** @var AsyncApiCacheManager 组件接口缓存管理器  */
    private $apiCacheManager;

    /** @var array 异步API信息列表 */
    private $asyncApiInfoList;

    /** @var array API接口配置 */
    private $asyncApiConfig = [];

    /** @var array API请求返回结果 */
    private $apiResultInfo = [];

    /** @var array API请求失败结果 */
    private $apiFailInfo = [];

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser 页面解析器
     * @param AsyncApiCacheManager $apiCacheManager 组件接口缓存管理器
     * @param array $asyncApiInfoList 异步API信息列表
     */
    public function __construct(PageUiAsyncDataParser $pageParser, AsyncApiCacheManager $apiCacheManager, $asyncApiInfoList)
    {
        $this->pageParser = $pageParser;
        $this->apiCacheManager = $apiCacheManager;
        $this->asyncApiInfoList = $asyncApiInfoList;
        $this->asyncApiConfig = AppHelpers::getAsyncApiConfig($this->pageParser->getPageInfo()->getSiteCode());
    }

    /**
     * 设置最大重试次数
     *
     * @param int $maxRetries
     */
    public function setMaxRetries($maxRetries)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * 是否有请求失败的API接口
     *
     * @return bool
     */
    public function hasFailed()
    {
        return !empty($this->apiFailInfo);
    }

    /**
     * 获取API请求返回结果
     * @return array
     */
    public function getApiResultInfo()
    {
        return $this->apiResultInfo;
    }

    /**
     * 并发请求API接口
     *
     * @param int $concurrency 并发数量
     */
    public function requestAllApi($concurrency = 5)
    {
        $handlerStack = HandlerStack::create();
        if ($this->maxRetries > 0) {
            // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
            $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        }

        $client = new Client(['handler' => $handlerStack]);
        $requests = $this->getAsyncRequests($client);
        $pool = new Pool($client, $requests, [
            'concurrency' => $concurrency,
            'fulfilled' => function (Response $response, $index) {
                $this->taskSuccess($response, $index);
            },
            'rejected' => function ($reason, $index) {
                $this->taskFail($reason, $index);
            },
        ]);
        $pool->promise()->wait();
    }

    /**
     * 获取请求列表
     *
     * @param Client $client 链接对象
     * @return \Generator
     */
    protected function getAsyncRequests(Client $client)
    {
        foreach ($this->asyncApiInfoList as $componentId => $uiApiInfoList) {
            foreach ($uiApiInfoList as $apiId => $apiInfo) {
                // 是否可以获取接口缓存数据
                if ($this->apiCacheManager->isSupportCache($apiInfo[SkuDataParser::KEY_API_NAME])) {
                    if ($this->tryGetApiResultFromCache($componentId, $apiId, $apiInfo)) {
                        continue;
                    }
                }

                // 没有缓存数据，请求接口
                yield $componentId .'_'. $apiId => function () use ($client, $apiInfo) {
                    return $this->buildRequest($client, $apiInfo);
                };
            }
        }
    }

    /**
     * 是否从缓存缓存获取数据
     *
     * @param int $componentId 组件ID
     * @param int $apiId 组件下的API请求ID
     * @param array $apiInfo API请求信息
     * @return bool
     */
    protected function tryGetApiResultFromCache($componentId, $apiId, $apiInfo)
    {
        $resultBody = $this->apiCacheManager->getCache($componentId, $apiId, $apiInfo);
        if (!empty($resultBody)) {
            $this->apiResultInfo[$componentId][$apiId] = AppHelpers::jsonDecode($resultBody, true);
            return true;
        }

        return false;
    }

    /**
     * 生成请求
     *
     * @param Client $client 链接对象
     * @param array $apiInfo API请求信息
     * @return PromiseInterface
     */
    private function buildRequest(Client $client, $apiInfo)
    {
        $apiName = $apiInfo[SkuDataParser::KEY_API_NAME];
        $apiParams = $apiInfo[SkuDataParser::KEY_API_PARAMS] ?? [];
        unset($apiInfo);

        // 获取API配置信息, ES 搜索API不走配置文件,这里手动配置
        if (ApiConstants::API_NAME_ES_SEARCH === $apiName) {
            $esSearch = $this->pageParser->getEsSearch();
            $configInfo = [
                'url' => $esSearch->getApiUrl(),
                'method' => 'post',
                'bodyType' => self::BODY_TYPE_FORM_RAW_JSON,
            ];
            $esSearch->buildRequestParams($apiParams);
            $apiParams = $esSearch->getApiParams();
            if (GES_ENABLE_TRACK_LOG) {
                echo AppHelpers::jsonEncode($apiParams) .PHP_EOL;
            }
            $logFormat = '解析组件数据，请求ES搜索API[%s - %s],参数: %s';
            ges_track_log(__CLASS__, $logFormat, $apiName, $configInfo['url'], $apiParams);
        } else {
            $configInfo = $this->asyncApiConfig[$apiName];
            if (GES_ENABLE_TRACK_LOG) {
                echo AppHelpers::jsonEncode($apiParams) .PHP_EOL;
            }
            $logFormat = '解析组件数据，请求API[%s - %s],参数: %s';
            ges_track_log(__CLASS__, $logFormat, $apiName, $configInfo['url'], $apiParams);
        }

        // 生成请求options
        $options = $this->getDefaultOptions($configInfo);
        $this->stagingApiRequest($options, $configInfo);

        $apiUrl = $configInfo['url'];
        // POST 请求
        if (strtolower($configInfo['method']) === 'post') {
            $this->postMethodBodyOptions($options, $configInfo, $apiParams);
            return $client->postAsync($apiUrl, $options);
        }

        // GET 请求
        $apiUrl = $this->getHttpQueryUrl($apiUrl, $apiParams);
        return $client->getAsync($apiUrl, $options);
    }

    /**
     * 请求成功
     *
     * @param Response $response http返回对象
     * @param string $key 请求索引key
     */
    protected function taskSuccess(Response $response, $key)
    {
        list($componentId, $apiId) = explode('_', $key, 2);
        $apiInfo = $this->asyncApiInfoList[$componentId][$apiId];
        $resultBody = $response->getBody()->getContents();
        $resultInfo = AppHelpers::jsonDecode($resultBody, true);
        $apiName = $apiInfo[SkuDataParser::KEY_API_NAME];

        // 检查接口返回是否成功
        try {
            $this->pageParser->getSiteApiResultTransformer()->checkUiComponentApiSuccessResult($apiInfo, $resultInfo);
        } catch (ApiRequestException $e) {
            $this->apiRequestFail($componentId, $apiId, $apiName, $e->getMessage());
            return;
        }

        // 接口返回成功流程
        if (ApiConstants::API_NAME_ES_SEARCH === $apiName) {
            if (!$this->pageParser->getEsSearch()->hasGoodsInfo($resultInfo)) {
                $apiParams = $apiInfo[SkuDataParser::KEY_API_PARAMS] ?? [];
                $esSearch = $this->pageParser->getEsSearch();
                $esSearch->buildRequestParams($apiParams);
                $apiParams = $esSearch->getApiParams();

                $logFormat = '解析组件数据,请求API[%s]没有商品数据。参数: %s 返回: %s';
                ges_warning_log(__CLASS__, $logFormat, $apiName, $apiParams, $resultBody);
                return;
            }
        }

        $this->apiResultInfo[$componentId][$apiId] = $resultInfo;
        ges_track_log(__CLASS__,'解析组件数据，请求API[%s]成功, 返回: %s', $apiName, $resultBody);

        // 设置数据缓存
        if ($this->apiCacheManager->isSupportCache($apiName)) {
            $this->apiCacheManager->setCache($componentId, $apiInfo, $resultBody);
        }
    }

    /**
     * 请求失败
     *
     * @param RequestException $reason 异常对象
     * @param string $key 请求索引key
     */
    protected function taskFail(RequestException $reason, $key)
    {
        list($componentId, $apiId) = explode('_', $key, 2);
        $apiInfo = $this->asyncApiInfoList[$componentId][$apiId];

        $this->apiRequestFail($componentId, $apiId, $apiInfo[SkuDataParser::KEY_API_NAME], $reason->getMessage());
    }

    /**
     * API接口请求失败
     *
     * @param int $componentId 组件ID
     * @param int $apiId 组件下的API请求ID
     * @param string $apiName API名称
     * @param string $message 错误信息
     */
    protected function apiRequestFail($componentId, $apiId, $apiName, $message)
    {
        $message = sprintf('解析组件数据，请求API[%s]失败, 原因: %s', $apiName, $message);
        $this->apiFailInfo[$componentId][$apiId] = $message;
        ges_warning_log(__CLASS__, $message);
        report(new ApiRequestException($message));
    }

    /**
     * 返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试
     * @return Closure
     */
    protected function retryDecider()
    {
        return function ($retries, Request $request, Response $response = null, RequestException $exception = null) {
            // 超过最大重试次数，不再重试
            if ($retries >= $this->maxRetries) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // 如果请求有响应，但是状态码大于等于500，继续重试(这里根据自己的业务而定)
                if ($response->getStatusCode() >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * 返回一个匿名函数，该匿名函数返回下次重试的时间（毫秒）
     * @return Closure
     */
    protected function retryDelay()
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }
}
