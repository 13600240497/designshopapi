<?php
namespace App\Services\Site;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\AppHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\AbstractGuzzleHttp;

/**
 * 站点API请求抽象类
 *
 * @author tianhaishen
 */
abstract class AbstractBaseApiRequest extends AbstractGuzzleHttp
{
    /** @var string 站点简码，如: zf-pc/zf-app */
    private $siteCode;

    /** @var array API接口配置 */
    private $asyncApiConfig = [];

    /**
     * 构造函数
     *
     * @param string $siteCode 站点简码，如: zf-pc/zf-app
     */
    public function __construct($siteCode)
    {
        $this->siteCode = $siteCode;
        $this->asyncApiConfig = AppHelpers::getAsyncApiConfig($siteCode);
    }

    /**
     * 请求接口
     *
     * @param string $apiName API配置名称
     * @param array $apiParams API参数
     * @return string 返回内容
     * @throws ApiRequestException
     */
    protected function requestApi($apiName, $apiParams)
    {
        if (!isset($this->asyncApiConfig[$apiName])) {
            throw new ApiRequestException(sprintf('API配置 %s 没有找到!', $apiName));
        }

        $configInfo = $this->asyncApiConfig[$apiName];
        $options = $this->getDefaultOptions($configInfo);
        $this->stagingApiRequest($options, $configInfo);
        $apiUrl = $configInfo['url'];
        $isPostMethod = strtolower($configInfo['method']) === 'post';

        try {
            $client = $this->getClient();
            if ($isPostMethod) {
                $this->postMethodBodyOptions($options, $configInfo, $apiParams);
                $response = $client->post($apiUrl, $options);
            } else {
                $apiUrl = $this->getHttpQueryUrl($apiUrl, $apiParams);
                $response = $client->get($apiUrl, $options);
            }
            $apiResBody = $response->getBody()->getContents();

            // 跟踪日志
            $logFormat = '请求API [ %s %s ] 返回： %s';
            $paramsString = ($isPostMethod && !empty($apiParams)) ? AppHelpers::jsonEncode($apiParams) : '';
            ges_track_log(__CLASS__, $logFormat, $apiUrl, $paramsString, $apiResBody);

            return $apiResBody;
        } catch (RequestException $e) {
            // 错误日志
            $paramsString = ($isPostMethod && !empty($apiParams)) ? AppHelpers::jsonEncode($apiParams) : '';
            $logFormat = '请求API [ %s %s ] 异常： %s';
            ges_warning_log(__CLASS__, $logFormat, $apiUrl, $paramsString, $e->getMessage());
            report($e);

            throw new ApiRequestException(sprintf('请求API [ %s ] 异常： %s', $apiUrl, $e->getMessage()), 0, $e);
        }
    }

    /**
     * 异步请求接口
     *
     * @param string $apiName API配置名称
     * @param array $apiParams API参数
     * @param Client $client 链接对象
     * @return PromiseInterface 返回内容
     * @throws ApiRequestException
     */
    protected function asyncRequestApi($apiName, $apiParams, Client $client)
    {
        if (!isset($this->asyncApiConfig[$apiName])) {
            throw new ApiRequestException(sprintf('API配置 % 没有找到!', $apiName));
        }

        $configInfo = $this->asyncApiConfig[$apiName];
        $options = $this->getDefaultOptions($configInfo);
        $this->stagingApiRequest($options, $configInfo);

        $isPostMethod = strtolower($configInfo['method']) === 'post';
        $apiUrl = $configInfo['url'];

        // POST 请求
        if ($isPostMethod) {
            $this->postMethodBodyOptions($options, $configInfo, $apiParams);
            return $client->postAsync($apiUrl, $options);
        }

        // GET 请求
        $apiUrl = $this->getHttpQueryUrl($apiUrl, $apiParams);
        return $client->getAsync($apiUrl, $options);
    }
}
