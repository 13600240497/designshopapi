<?php
namespace App\Helpers;

use Closure;
use Illuminate\Support\Str;
use App\Base\AppConstants;

/**
 * 应用相关函数
 *
 * @author TianHaisen
 */
class AppHelpers
{
    /** @var array API配置缓存 */
    private static $asyncApiConfig = [];

    /**
     * 获取服务器环境
     *
     * @return string
     */
    public static function getEnv()
    {
        return app()->environment();
    }

    /**
     * 判断是否是生产环境
     *
     * @return bool
     */
    public static function isProductionEnv()
    {
        return self::getEnv() === 'production';
    }

    /**
     * 判断是否是预发布环境
     *
     * @return bool
     */
    public static function isStagingEnv()
    {
        return self::getEnv() === 'staging';
    }

    /**
     * 判断是否是测试环境
     *
     * @return bool
     */
    public static function isTestEnv()
    {
        return self::getEnv() === 'test';
    }

    /**
     * json 编码
     *
     * @param mixed $value
     * @param int $options
     * @return false|string
     */
    public static function jsonEncode($value, $options = 0)
    {
        return json_encode($value, $options);
    }

    /**
     * json 解码
     *
     * @param string $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     */
    public static function jsonDecode($json, $assoc = false, $depth = 512, $options = 0)
    {
        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * 压缩数据
     *
     * @param string $data 数据
     * @return string
     * @see \gzcompress
     */
    public static function compress($data)
    {
        return gzcompress($data, 9);
    }

    /**
     * 解压缩数据
     *
     * @param string $data 数据
     * @return string
     */
    public static function uncompress($data)
    {
        return gzuncompress($data);
    }


    /**
     * Notes:从多维数据中返回指定key键值的一维数组
     * User: zhuguoqiang
     * DateTime: 2020-04-29
     * @param array $arr 多维数组
     * @param $key 提取字段键值
     * @return array 一维数组
     */
    public static function getArrayByKey(array $arr, $key)
    {
        $list = [];
        foreach ($arr as $i => $val) {
              if (!is_array($val)) {
                  if ($i === $key)  array_push($list, $val);
              } else {
                  $value = static::getArrayByKey($val, $key);
                  $list = array_merge($list, $value);
              }
        }
        return $list;

    }

    /**
     * 根据站点获取API配置信息
     *
     * @param string $siteCode
     * @return array
     */
    public static function getAsyncApiConfig($siteCode)
    {
        if (!isset(self::$asyncApiConfig[$siteCode])) {
            $asyncApiConfig = [];
            list($websiteCode, $platformCode) = SiteHelpers::splitSiteCode($siteCode);
            $_key = (AppConstants::PLATFORM_CODE_PC === $platformCode) ? 'PC' : 'M';
            $apiPrefix = env(sprintf('%s_%s_API_PREFIX', strtoupper($websiteCode), $_key));
            $apiConfig = config('asyncapi');
            foreach ($apiConfig as $name => $apiInfo) {
                if (in_array($siteCode, $apiInfo['support'], true)) {
                    if (!Str::startsWith($apiInfo['url'], 'http')) {
                        $apiInfo['url'] = $apiPrefix . $apiInfo['url'];
                    }
                    unset($apiInfo['support'], $apiInfo['description']);
                    $asyncApiConfig[$name] = $apiInfo;
                }
            }
            self::$asyncApiConfig[$siteCode] = $asyncApiConfig;
        }

        return self::$asyncApiConfig[$siteCode];
    }

    /**
     * 获取Redis缓存数据
     *
     * @param string $key 缓存key
     * @param Closure $loaderCallback 数据加载回调函数
     * @param int $seconds 过期时间，单位秒,默认0
     * @param bool $useCompress 是否使用压缩
     * @return array
     */
    public static function getArrayCacheIfPresent($key, Closure $loaderCallback, $useCompress = false, $seconds = -1)
    {
        $redis = ContainerHelpers::getPredisResolveCache();
        $data = $redis->get($key);
        if (empty($data)) {
            $data = call_user_func($loaderCallback);
            if (!empty($data) && is_array($data)) {
                $dataJson = self::jsonEncode($data);
                if ($seconds > 0) {
                    $redis->setex($key, $seconds, $useCompress ? self::compress($dataJson) : $dataJson);
                } else {
                    $redis->set($key, $useCompress ? self::compress($dataJson) : $dataJson);
                }
                ges_track_log(__CLASS__, '设置Redis键名为[%s],缓存数据: %s', $key, $dataJson);
            }
        } else {
            $dataString = $useCompress ? self::uncompress($data) : $data;
            $data = self::jsonDecode($dataString, true);
            ges_track_log(__CLASS__, '从Redis键名为[%s],获取到数据: %s', $key, $dataString);
        }

        return $data;
    }
}
