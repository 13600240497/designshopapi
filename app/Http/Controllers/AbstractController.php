<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller 层抽象类
 *
 * @author TianHaisen
 */
abstract class AbstractController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $route = explode('/',app('request')->route()->uri);
        if ($route[1] != 'log') ges_track_log(__CLASS__, ges_common_log());
    }


    /**
     * 获取组件Http参数
     *
     * @param array $paramsRefer Http参数
     * @param array $keys 组件支持的参数名称列表
     * @return array
     */
    protected function getComponentHttpParams(array &$paramsRefer, array $keys)
    {
        if (empty($paramsRefer) || empty($keys)) {
            return [];
        }

        $params = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $paramsRefer)) {
                $params[$key] = $paramsRefer[$key];
            }
        }
        return $params;
    }
}
