<?php
namespace App\Http\Controllers\Common;

use App\Http\Controllers\AbstractWebController;

/**
 * 首页
 *
 * @author TianHaisen
 */
class IndexController extends AbstractWebController
{
    /**
     * 首页
     */
    public function index()
    {
        return $this->jsonSuccess();
    }
}
