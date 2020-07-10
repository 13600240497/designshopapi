<?php
namespace App\Gadgets\Rms;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psy\Exception\ErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Helpers\AppHelpers;

/**
 * RMS监控系统对接类
 *
 * @author laifufeng
 */
class Rms
{
    /**
     * @var int 级别为0
     */
    const LEVEL0 = 0;
    /**
     * @var int 级别为1
     */
    const LEVEL1 = 1;
    /**
     * @var int 级别为1
     */
    const LEVEL2 = 2;
    /**
     * @var int 级别为3
     */
    const LEVEL3 = 3;
    /**
     * @var int 级别为4
     */
    const LEVEL4 = 4;
    /**
     * @var int 级别为5
     */
    const LEVEL5 = 5;
    /**
     * @var int 级别为6
     */
    const LEVEL6 = 6;
    /**
     * @var int 级别为7
     */
    const LEVEL7 = 7;

    /**
     * @var string PHP错误监控简码
     */
    const ERROR_PHP = 'WFZ25228';
    /**
     * @var string MYSQL错误监控简码
     */
    const ERROR_MYSQL = 'WFZ24418';

    const ERROR_LEVEL = 'level';

    /**
     * @var array 监控简码映射信息
     */
    public static $code2name = [
        self::ERROR_PHP   => 'PHP错误监控',
        self::ERROR_MYSQL => 'MYSQL错误监控'
    ];

    /**
     * php错误与自定义错误码的映射表
     *
     * ``` 监控代码
     * 100001    PHP致命错误
     * 100002    PHP警告错误
     * 100003    PHP语法错误
     * 100005    PHP内核致命错误
     * 100006    PHP内核警告错误
     * 100009    PHP用户触发致命错误
     * 100010    PHP用户触发警告错误
     * ```
     * ``` PHP预定义常量
     * 1        E_ERROR         致命的运行时错误。这类错误一般是不可恢复的情况，例如内存分配导致的问题。后果是导致脚本终止不再继续运行。
     * 2        E_WARNING       运行时警告 (非致命错误)。仅给出提示信息，但是脚本不会终止运行。
     * 4        E_PARSE         编译时语法解析错误。解析错误仅仅由分析器产生。
     * 8        E_NOTICE        运行时通知。表示脚本遇到可能会表现为错误的情况，但是在可以正常运行的脚本里面也可能会有类似的通知。
     * 16       E_CORE_ERROR    在PHP初始化启动过程中发生的致命错误。该错误类似 E_ERROR，但是是由PHP引擎核心产生的。
     * 32       E_CORE_WARNING  PHP初始化启动过程中发生的警告 (非致命错误) 。类似 E_WARNING，但是是由PHP引擎核心产生的。
     * 64       E_COMPILE_ERROR 致命编译时错误。类似E_ERROR, 但是是由Zend脚本引擎产生的。
     * 128      E_COMPILE_WARNING    编译时警告 (非致命错误)。类似 E_WARNING，但是是由Zend脚本引擎产生的。
     * 256      E_USER_ERROR    用户产生的错误信息。类似 E_ERROR, 但是是由用户自己在代码中使用PHP函数 trigger_error()来产生的。
     * 512      E_USER_WARNING  用户产生的警告信息。类似 E_WARNING, 但是是由用户自己在代码中使用PHP函数 trigger_error()来产生的。
     * 1024     E_USER_NOTICE   用户产生的通知信息。类似 E_NOTICE, 但是是由用户自己在代码中使用PHP函数 trigger_error()来产生的。
     * 2048     E_STRICT        启用 PHP 对代码的修改建议，以确保代码具有最佳的互操作性和向前兼容性。
     * 4096     E_RECOVERABLE_ERROR    可被捕捉的致命错误。 它表示发生了一个可能非常危险的错误，但是还没有导致PHP引擎处于不稳定的状态。
     * 如果该错误没有被用户自定义句柄捕获 (参见 set_error_handler())，将成为一个 E_ERROR　从而脚本会终止运行。
     * 8192     E_DEPRECATED    运行时通知。启用后将会对在未来版本中可能无法正常工作的代码给出警告。
     * 16384    E_USER_DEPRECATED    用户产少的警告信息。 类似 E_DEPRECATED, 但是是由用户自己在代码中使用PHP函数 trigger_error()来产生的。
     * 30719    E_ALL           E_STRICT出外的所有错误和警告信息。
     * ```
     *
     * @link http://php.net/manual/zh/errorfunc.constants.php
     * @var array
     */
    public static $phpCode2CustomCodeInfo = [
        E_ERROR             => ['code' => 100001, self::ERROR_LEVEL => self::LEVEL2],
        E_WARNING           => ['code' => 100001, self::ERROR_LEVEL => self::LEVEL5],
        E_NOTICE            => ['code' => 100001, self::ERROR_LEVEL => self::LEVEL6],
        E_PARSE             => ['code' => 100003, self::ERROR_LEVEL => self::LEVEL2],
        E_CORE_ERROR        => ['code' => 100005, self::ERROR_LEVEL => self::LEVEL2],
        E_CORE_WARNING      => ['code' => 100006, self::ERROR_LEVEL => self::LEVEL5],
        E_COMPILE_ERROR     => ['code' => 100007, self::ERROR_LEVEL => self::LEVEL1],
        E_COMPILE_WARNING   => ['code' => 100008, self::ERROR_LEVEL => self::LEVEL6],
        E_USER_ERROR        => ['code' => 100009, self::ERROR_LEVEL => self::LEVEL2],
        E_USER_WARNING      => ['code' => 100010, self::ERROR_LEVEL => self::LEVEL5],
        E_USER_NOTICE       => ['code' => 100011, self::ERROR_LEVEL => self::LEVEL7],
        E_STRICT            => ['code' => 100012, self::ERROR_LEVEL => self::LEVEL7],
        E_RECOVERABLE_ERROR => ['code' => 100013, self::ERROR_LEVEL => self::LEVEL7],
        E_DEPRECATED        => ['code' => 100014, self::ERROR_LEVEL => self::LEVEL7],
        E_USER_DEPRECATED   => ['code' => 100015, self::ERROR_LEVEL => self::LEVEL7],
        E_ALL               => ['code' => 100016, self::ERROR_LEVEL => self::LEVEL7],
    ];

    /**
     * mysql错误与自定义错误码的映射表
     *
     * ```  监控代码
     * 100100    MySQL其他类型错误
     * 100101    数据库连接失败
     * 100102    数据表不存在
     * 100103    SQL命令无权限执行
     * 100104    表字段不存在
     * 100105    SQL语法错误
     * 100106    数据库连接超时
     * 100107    MySQL数据插入，键冲突，插入失败
     * 100108    MySQL查询超时，PHP程序主动停止MySQL查询进程
     * 100109    MySQL查询进程，被系统停止
     * 100110    MySQL数据库连接，账号无权限
     * 100111    MySQL数据表锁定，会自动尝试恢复
     * 100112    MySQL未选择数据库，常见伴随着 数据库账号无权限看不到数据库
     * ```
     * ``` mysql系统代码
     * 1213    Deadlock found when trying to get lock; try restarting transaction[40001 (ER_LOCK_DEADLOCK)]
     * 1317    Query execution was interrupted[ 70100 (ER_QUERY_INTERRUPTED)]
     * 1044    Access denied for user '%s'@'%s' to database '%s'[42000 (ER_DBACCESS_DENIED_ERROR)]
     * 1046    No database selected[3D000 (ER_NO_DB_ERROR)]
     * 1049    Unknown database '%s'[42000 (ER_BAD_DB_ERROR)]
     * 1060    Duplicate column name '%s'[42S21 (ER_DUP_FIELDNAME)]
     * 1064    %s near '%s' at line %d[42000 (ER_PARSE_ERROR)]
     * 1065    INSERT DELAYED can't be used with table '%s' because it is locked with LOCK TABLES
     * [HY000 (ER_DELAYED_INSERT_TABLE_LOCKED)]
     * 1099    Table '%s' was locked with a READ lock and can't be updated[HY000 (ER_TABLE_NOT_LOCKED_FOR_WRITE)]
     * 1100    Table '%s' was not locked with LOCK TABLES[HY000 (ER_TABLE_NOT_LOCKED)]
     * 1142    %s command denied to user '%s'@'%s' for table '%s'[42000 (ER_TABLEACCESS_DENIED_ERROR)]
     * 1143    %s command denied to user '%s'@'%s' for column '%s' in table '%s'[42000 (ER_COLUMNACCESS_DENIED_ERROR)]
     * 1146    Table '%s.%s' doesn't exist[42S02 (ER_NO_SUCH_TABLE)]
     * 1156    Unknown column '%s' in '%s'[42S22 (ER_BAD_FIELD_ERROR)]
     * 2003    Can't connect to MySQL server on '%s' (%d)[2003 (CR_CONN_HOST_ERROR)]
     * 2013    Lost connection to MySQL server during query[2013 (CR_SERVER_LOST)]
     * ```
     *
     * @link https://dev.mysql.com/doc/refman/5.5/en/error-messages-client.html
     * @link https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
     * @var array
     */
    public static $mysqlCode2CustomCodeInfo = [
        1049    => ['code' => 100101, self::ERROR_LEVEL => self::LEVEL2],
        1146    => ['code' => 100102, self::ERROR_LEVEL => self::LEVEL1],
        1142    => ['code' => 100103, self::ERROR_LEVEL => self::LEVEL2],
        1143    => ['code' => 100103, self::ERROR_LEVEL => self::LEVEL2],
        1054    => ['code' => 100104, self::ERROR_LEVEL => self::LEVEL1],
        1064    => ['code' => 100105, self::ERROR_LEVEL => self::LEVEL2],
        2003    => ['code' => 100106, self::ERROR_LEVEL => self::LEVEL2],
        1060    => ['code' => 100107, self::ERROR_LEVEL => self::LEVEL2],
        1317    => ['code' => 100108, self::ERROR_LEVEL => self::LEVEL2],
        2013    => ['code' => 100109, self::ERROR_LEVEL => self::LEVEL2],
        1044    => ['code' => 100110, self::ERROR_LEVEL => self::LEVEL0],
        1213    => ['code' => 100111, self::ERROR_LEVEL => self::LEVEL2],
        1099    => ['code' => 100111, self::ERROR_LEVEL => self::LEVEL2],
        1065    => ['code' => 100111, self::ERROR_LEVEL => self::LEVEL2],
        1100    => ['code' => 100111, self::ERROR_LEVEL => self::LEVEL2],
        1046    => ['code' => 100112, self::ERROR_LEVEL => self::LEVEL0],
        'other' => ['code' => 100100, self::ERROR_LEVEL => self::LEVEL3],
    ];

    /**
     * @var string 请求的url
     */
    public $url;
    /**
     * @var string token认证信息
     */
    public $token;

    /**
     * @var string redisKey 设置至redis的hash键值
     */
    public $redisKey;

    /**
     * @var string metadata文件路径--路径由运维那边提供
     */
    public $metadataFile;

    /**
     * @var string metadata信息请求url--该地址是固定的，不需要修改参数和值
     */
    public $metadataUrl;

    /**
     * @var
     */
    public $server = null;

    public function __construct ()
    {
        $this->redisKey = config('cache.rms_error_key');
        $this->metadataFile = '/tmp/metadata_tags';
    }

    /**
     * 监听异常信息
     *
     * @param \Exception $exception the exception that is not caught
     */
    public function observeException ($exception)
    {
        $this->observeProgrammerException($exception);
    }

    /**
     * 监听程序异常信息
     * [mysql错误 和 php错误]
     *
     * @param \Exception $exception the exception that is not caught
     */
    private function observeProgrammerException ($exception)
    {
        $pointCode = '';
        /**
         * @var array $errorInfo 错误代码信息
         * ```
         * ['code' => '242342342', self::ERROR_LEVEL => 1]
         * ```
         */
        $errorInfo = [];
        if ($exception instanceof \PDOException) {
            // capture exception of DB
            $systemCode = $exception->errorInfo[1] ?? 0;
            if (0 === $systemCode) {
                $systemCode = $exception->getCode();
            }
            $errorInfo = self::$mysqlCode2CustomCodeInfo[$systemCode] ?? self::$mysqlCode2CustomCodeInfo['other'];
            $pointCode = self::ERROR_MYSQL;
        } elseif ($exception instanceof ErrorException || $exception instanceof \Error
            || $exception instanceof \Exception
        ) {
            // exception of PHP
            // PHP在7.0版本以上错误会抛出为Error类
            // 系统所有PHP错误都会抛出异常，显示错误页面，破坏程序流程，因而都定义为错误
            $errorInfo = ['code' => 100001, self::ERROR_LEVEL => self::LEVEL2];
            $pointCode = self::ERROR_PHP;
        }
        if (empty($pointCode) || !is_array($errorInfo)) {
            return;
        }

        /**
         * 组合监控数据
         */
        $data['server_name'] = $this->getServeIdentifies();
        $serverIpTemp = explode('_', $data['server_name']);
        $data['server_ip'] = end($serverIpTemp);

        /**
         * 错误明细格式
         * 错误消息 + 请求URI + GET数据 + POST数据
         */
        $errorDetail = [
            'REQUEST URL[' . \request()->url() . ']',
            'REQUEST GET[' . var_export($_GET, true) . ']',
            'REQUEST POST[' . var_export($_POST, true) . ']',
            $exception->getFile() . '(' . $exception->getLine() . ' )',
            $exception->getMessage(),
            $exception->getTraceAsString(),
        ];

        $data['content'] = [
            // 40000字以内
            'info' => '[geshop-api]' . implode("\r\n", $errorDetail)
        ];
        $data['notice_time'] = Carbon::now('asia/shanghai')->toDateTimeString();;
        $buildData = $this->buildErrorData($pointCode, $errorInfo, $data);
        // 监控异常数据加入Redis
        $this->pushToRedis($buildData, $pointCode, array_slice($errorDetail, 0, 3));
    }

    /**
     * 组装一下错误消息数据
     *
     * @param $pointCode
     * @param $errorInfo
     * @param $data
     *
     * @return array
     */
    private function buildErrorData ($pointCode, $errorInfo, $data)
    {
        $buildData = [
            'point_code'      => $pointCode,
            'error_code'      => $errorInfo['code'],
            'server_ip'       => $data['server_ip'],
            'server_name'     => $data['server_name'],
            // 手动获取北京时区时间戳
            'notice_time'     => $data['notice_time'] . "\r\n",
            self::ERROR_LEVEL => $errorInfo[self::ERROR_LEVEL],
            'is_test'         => 0 . "\r\n",
            'content'         => $data['content']
        ];
        log::channel('dailyError')->error('push to Redis', $buildData);
        log::channel('singleError')->error('push to Redis', $buildData);

        return $buildData;
    }

    /**
     * 根据服务器环境变量获取项目所在的环境
     * 格式：域名_平台_环境_ip；英文全部为小写
     *
     * @return string
     */
    private function getServeIdentifies ()
    {
        $env = AppHelpers::getEnv();
        if ('local' == strtolower($env)) {
            return 'dev_localhost_' . ($_SERVER['SERVER_ADDR'] ?? null) . '_0';
        }

        $serverName = '';
        if (is_readable($this->metadataFile)) {
            $metadataInfo = file_get_contents($this->metadataFile);
        } else {
            if (!empty($this->metadataUrl)) {
                $options = [
                    RequestOptions::VERIFY => false,
                    RequestOptions::TIMEOUT => 3
                ];
                try {
                    $response = (new Client())->get($this->metadataUrl, $options);
                    $metadataInfo = $response->getBody()->getContents();
                } catch (RequestException $e) {
                    $metadataInfo = '';
                }
            } else {
                $metadataInfo = '';
            }
        }

        if (!empty($metadataInfo)) {
            $metadataInfoList = explode(':', $metadataInfo);
            $serverName = count($metadataInfoList) < 2
                ? $metadataInfoList[0]
                : $metadataInfoList[1];
        }

        return strtolower(trim($serverName));
    }

    /**
     * 追加监控点信息到MQ
     *
     * @param array  $data      监控数据
     *                          ```
     *                          array (
     *                          'point_code' => 'WFZ24418',
     *                          'error_code' => 100102,
     *                          'server_ip' => '192.168.6.72',
     *                          'server_name' => 'dev_localhost_192.168.6.72',
     *                          'notice_time' => '2017-05-15 14:48:52',
     *                          'content' => array (
     *                          'info' => 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'girlbest_db.A\'
     *                          doesn\'t exist The SQL being executed was: SELECT * FROM A
     *                          /disk2/dev72/laifufeng/girlbest-service-b/vendor/yiisoft/yii2/db/Schema.php( 636 )
     *                          REQUEST URL[http://www.girlbest-service-b.com.laifufeng.dev72.egocdn.com/promotion/wefffw-fwe-53/]
     *                          REQUEST GET[array ( \'component\' => \'wefffw-fwe-53\', \'temp\' => \'\', )]
     *                          REQUEST POST[array ( )]'
     *                          ),
     *                          self::ERROR_LEVEL => 1,
     *                          'is_test' => 0
     *                          )
     *                          ```
     * @param string $pointCode 监控点
     * @param array  $salt      加密的salt  用来生成hash key值,结合@pointCode生成唯一的key
     *
     * @return bool
     */
    protected function pushToRedis (array $data, $pointCode, array $salt)
    {
        if (isset($data['point_code'])) {
            array_unshift($salt, $pointCode);
            $key = md5(implode('', $salt));
            $item = app('predis')->resolve('rms')->hget($this->redisKey, $key);
            $item || app('predis')->resolve('rms')->hset($this->redisKey, $key, json_encode($data));
        }
    }
}

