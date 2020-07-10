<?php
/**
 * Redis key的异常管理.
 */

namespace App\Gadgets\Rdkey;


use Throwable;

class RdkeyException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
