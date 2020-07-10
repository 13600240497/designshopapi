<?php
namespace App\Http\Controllers\Common;

use App\Http\Controllers\AbstractWebController;

/**
 * 健康检查
 *
 * @author TianHaisen
 */
class HealthController extends AbstractWebController
{

    /**
     * 健康检查方法
     */
    public function check()
    {
        echo 'Hello Word';
    }
}
