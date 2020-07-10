<?php
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use App\Helpers\AppHelpers;

require __DIR__.'/vendor/autoload.php';

function requestApi()
{
    $params= [
        'pipeline' => 'zfie',
        'skus' => '459492601,459492001,459796703,460086503,460516001,459628508,457790605,454588801,286810101,459960213,281061301,283740911,455886708,453298709,459455412,281356503,457748705,262510417,368699402,455356310,457544501,454429602,459421301,260874013,454316709,454372101,460066601,276794801,277283303,455673601,251411109,458172310,451320203,453013009,459006201,354518201,407863704,458120902,214581103,457984102,460047501,208326507,281539401,452833901,238029912,459563401,211973303,223092301,455614601,455532108,314544402,455614801,458266505,220230828,226386506,454281104,254633302,309453401,403259502,455003601,211973302,458525708,454822007,271387004,454278501,451321304,455094701,454717510,326630802,263118701,454963101,456317902,240565401,377302002,455272601,452638502,363445401,457993402,212538802,352956401',
        'isShowDetail' => 0
    ];
    $client = new Client();
    $configInfo = [
        'url' => 'https://m.zaful.com/fun/index.php?act=discountPrice',
        'method' => 'post',
        'description' => 'zaful站点价格接口',
        'support' => ['zf-wap', 'zf-app']
    ];

    $options = [
        RequestOptions::VERIFY => false,
        RequestOptions::TIMEOUT => 3,
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
        ]
    ];

    $apiUrl = $configInfo['url'];
//    if (AppHelpers::isStagingEnv()) {
//        $apiDomain = parse_url($apiUrl, PHP_URL_HOST);
//        $cookieJar = CookieJar::fromArray(
//            ['staging' => 'true'],
//            mb_substr($apiDomain, stripos($apiDomain, '.'))
//        );
//        $options[ RequestOptions::COOKIES ] = $cookieJar;
//    }

    try {
        $isPostMethod = strtolower($configInfo['method']) === 'post';
        if ($isPostMethod) {
            if (isset($configInfo['enctype']) && ('rawJson' === $configInfo['enctype'])) {
                $options[RequestOptions::HEADERS] = [
                    'Content-type' => 'application/json',
                    'Accept' => 'application/json',
                    'Pragma' => 'no-cache',
                ];
                !empty($params) && $options[RequestOptions::JSON] = $params;
            } else {
                !empty($params) && $options[RequestOptions::FORM_PARAMS] = $params;
            }
            $response = $client->post($apiUrl, $options);
        } else {
            if (!empty($params) && is_array($params)) {
                $apiUrl .= (strpos($apiUrl, '?') === false) ? '?' : '&';
                $apiUrl .= http_build_query($params);
            }
            $response = $client->get($apiUrl, $options);
        }

        // 跟踪日志
        $apiResBody = $response->getBody()->getContents();
        $format = '请求API [ %s %s ] 返回： %s';
        $paramsString = ($isPostMethod && !empty($params)) ? AppHelpers::jsonEncode($params) : '';
        //ges_track_log(__CLASS__, $format, $apiUrl, $paramsString, $apiResBody);

        echo $apiResBody;
    } catch (RequestException $e) {
        // 错误日志
        $paramsString = ($isPostMethod && !empty($params)) ? AppHelpers::jsonEncode($params) : '';
        //ges_error_log(__CLASS__, '请求API [ %s %s ] 异常： %s', $apiUrl, $paramsString, $e->getMessage());

        echo sprintf('请求API [ %s ] 异常： %s', $apiUrl, $e->getMessage());
    }
}

requestApi();