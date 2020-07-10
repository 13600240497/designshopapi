<?php
namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * 应用异常，所有异常基类
 *
 * @author tianhaishen
 */
class AppException extends Exception
{
    /** @var bool 是否在全局异常报告里写本地日志 */
    protected $isLogInReport;

    /**
     * 应用异常初始化的过程，尝试基于错误Code做多语言翻译处理
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     * @param bool           $isLogInReport
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, $isLogInReport = false)
    {
        parent::__construct($message, $code, $previous);
        $this->isLogInReport = $isLogInReport;
    }

    /**
     * 是否在全局异常报告里写本地日志
     *
     * @return bool
     */
    public function isLogInReport()
    {
        return $this->isLogInReport;
    }
}
