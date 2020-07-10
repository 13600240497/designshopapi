<?php
/**
 * 项目全局函数
 *
 * 功能函数风格，自定义应用功能函数统一追加`ges_`前缀
 *
 * @author tianhaishen
 */


/**
 * 只有 ENABLE_INFO_LOG 为true的情况下才记录日志
 *
 * @param string $className 类名称
 * @param string $message 日志内容，可以使用 sprintf 函数 %s 等占位符
 * @param array $params 可变日志参数
 */
function ges_track_log($className, $message, ...$params)
{
    // 正式环境没有启用日志跟踪模式下不记录日志
    /*if (\App\Helpers\AppHelpers::isProductionEnv() && !GES_ENABLE_TRACK_LOG) {
        return;
    }*/

    if (!empty($params) && is_array($params)) {
        $params = array_map(function ($item) {
            return is_array($item) ? json_encode($item) : $item;
        }, $params);
        $message = sprintf($message, ...$params);
    }

    !empty($className) && $message = $className .' '. $message;
    $message = '[' . $_SERVER['SERVER_ADDR'] . '][' . Illuminate\Support\Carbon::now('asia/shanghai')
            ->toDateTimeString() . ']' . $message . "\r\n";
    \Illuminate\Support\Facades\Log::info($message);
}


/**
 * 业务警告日志
 *
 * @param string $className 类名称
 * @param string $message 日志内容，可以使用 sprintf 函数 %s 等占位符
 * @param array $params 可变日志参数
 */
function ges_warning_log($className, $message, ...$params)
{
    if (!empty($params) && is_array($params)) {
        $params = array_map(function ($item) {
            if ($item instanceof \Throwable) {
                $format = "%s in %s line %d trace:\n%s, %s";
                return sprintf($format, $item->getMessage(), $item->getFile(), $item->getLine(),
                    $item->getTraceAsString());
            }
            return is_array($item) ? json_encode($item) : $item;
        }, $params);
        $message = sprintf($message, ...$params);
    }

    !empty($className) && $message = $className .' '. $message;
    $message = '[' . $_SERVER['SERVER_ADDR'] . '][' . Illuminate\Support\Carbon::now('asia/shanghai')
            ->toDateTimeString() . ']' . $message . "\r\n";
    \Illuminate\Support\Facades\Log::channel('dailyWarning')->warning($message);
    \Illuminate\Support\Facades\Log::channel('singleWarning')->warning($message);
}

/**
 * 错误日志
 *
 * @param string $className 类名称
 * @param string $message 日志内容，可以使用 sprintf 函数 %s 等占位符
 * @param array $params 可变日志参数
 */
function ges_error_log($className, $message, ...$params)
{
    if (!empty($params) && is_array($params)) {
        $params = array_map(function ($item) {
            if ($item instanceof \Throwable) {
                $format = "%s in %s line %d trace:\n%s";
                return sprintf($format, $item->getMessage(), $item->getFile(), $item->getLine(),
                    $item->getTraceAsString());
            }
            return is_array($item) ? json_encode($item) : $item;
        }, $params);
        $message = sprintf($message, ...$params);
    }

    !empty($className) && $message = $className .' '. $message;
    \Illuminate\Support\Facades\Log::channel('dailyError')->error($message);
    \Illuminate\Support\Facades\Log::channel('singleError')->error($message);
}

function ges_common_log()
{
    return '[' . $_SERVER['SERVER_ADDR'] . '][' . Illuminate\Support\Carbon::now('asia/shanghai')
           ->toDateTimeString() . ']' . "\r\n". 'REQUEST URL[' . \request()->url() . ']' . "\r\n" .
           'REQUEST GET[' . var_export($_GET, true) . ']' . "\r\n".
           'REQUEST POST[' . var_export($_POST, true) . ']' . "\r\n"
          ;
}

