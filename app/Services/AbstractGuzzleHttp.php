<?php
namespace App\Services;

use App\Helpers\AppHelpers;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

/**
 * GuzzleHttp 请求抽象类
 *
 * @author TianHaisen
 */
abstract class AbstractGuzzleHttp
{
    /** @var string POST body类型 - form-data */
    const BODY_TYPE_FORM_DATA = 'form-data';

    /** @var string POST body类型 - form-data-urlencoded */
    const BODY_TYPE_FORM_DATA_URLENCODED = '';

    /** @var string POST body类型 - raw json格式 */
    const BODY_TYPE_FORM_RAW_JSON = 'raw-json';

    /** @var string 请求站点接口时特殊UA，其他UA会触发CDN请求拦截（注意!!!!） */
    const USER_AGENT = '$0tppv$rhCIfzJ*ng$U5J4#us#%buJSbg4m1OXuOf%!Pk%$ZnTEzSz*Od0s9v9B%';

    /** @var int 默认连接超时时间,单位秒 */
    const DEFAULT_TIMEOUT = 3;

    /** @var Client 链接对象 */
    private static $client = null;

    /**
     * 获取http连接对象
     *
     * @return Client
     */
    protected function getClient()
    {
        if (self::$client == null) {
            self::$client = new Client();
        }
        return self::$client;
    }

    /**
     * 处理POST请求， body 不同类型下，选项参数处理
     *
     * @param array $optionsRefer 项参数引用
     * @param array $apiConfig API配置信息
     * @param array $apiParams API请求参数
     */
    protected function postMethodBodyOptions(&$optionsRefer, $apiConfig, $apiParams)
    {
        if (isset($apiConfig['bodyType']) && (self::BODY_TYPE_FORM_RAW_JSON === $apiConfig['bodyType'])) {
            $headers = [
                'Content-type' => 'application/json',
                'Accept' => 'application/json',
                'Pragma' => 'no-cache',
            ];
            if (isset($optionsRefer[RequestOptions::HEADERS])) {
                $optionsRefer[RequestOptions::HEADERS] = array_merge($optionsRefer[RequestOptions::HEADERS], $headers);
            } else {
                $optionsRefer[RequestOptions::HEADERS] = $headers;
            }
            !empty($apiParams) && $optionsRefer[RequestOptions::JSON] = $apiParams;
        } else {
            !empty($apiParams) && $optionsRefer[RequestOptions::FORM_PARAMS] = $apiParams;
        }
    }

    /**
     * 获取基本请求选项
     *
     * @param int $timeout 请求超时
     * @return array
     */
    protected function getBaseOptions($timeout = 0)
    {
        return [
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => empty($timeout) ? self::DEFAULT_TIMEOUT : $timeout,
        ];
    }

    /**
     * 获取默认请求选项
     *
     * @param array $apiConfig API配置信息
     * @return array
     */
    protected function getDefaultOptions($apiConfig)
    {
        $timeout = $apiConfig['timeout'] ?? self::DEFAULT_TIMEOUT;
        $options = $this->getBaseOptions($timeout);
        $options[RequestOptions::HEADERS] = [
            'User-Agent' => self::USER_AGENT,
        ];

        return $options;
    }

    /**
     * 预发发布环境请求对应预发布环境API接口
     *
     * @param array $optionsRefer 项参数引用
     * @param array $apiConfig API配置信息
     */
    protected function stagingApiRequest(&$optionsRefer, $apiConfig)
    {
        if (AppHelpers::isStagingEnv()) {
            $apiDomain = parse_url($apiConfig['url'], PHP_URL_HOST);
            $cookieJar = CookieJar::fromArray(
                ['staging' => 'true'],
                mb_substr($apiDomain, stripos($apiDomain, '.'))
            );
            $optionsRefer[ RequestOptions::COOKIES ] = $cookieJar;
        }
    }

    /**
     * 获取完整GET请求URL
     *
     * @param string $url 请求URL
     * @param array $params 参数
     * @return string
     */
    protected function getHttpQueryUrl($url, $params)
    {
        if (!empty($params) && is_array($params)) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= http_build_query($params);
        }
        return $url;
    }
}
