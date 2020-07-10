<?php
namespace App\Exceptions;

use Throwable;

/**
 * API接口调用异常
 *
 * @author tianhaishen
 */
class ApiRequestException extends AppException
{
    /**
     * 应用异常初始化的过程，尝试基于错误Code做多语言翻译处理
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous

     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous, false);
    }
}
