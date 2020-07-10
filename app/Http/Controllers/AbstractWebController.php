<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * PC/M端接口抽象类
 *
 * @author TianHaisen
 */
abstract class AbstractWebController extends AbstractController
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->middleware('cross-request');
        parent::__construct();
    }

    /**
     * @param string $message
     * @param int $code
     * @param array $data
     * @return JsonResponse
     */
    protected function jsonFail($message = 'fail', $code = 1, $data = [])
    {
        $jsonRes = [
            'code'    => $code,
            'message' => $message,
        ];
        !empty($jsonRes) && $jsonRes['data'] = $data;

        return response()->json($jsonRes);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    protected function jsonSuccess($data = [])
    {
        $callback = request()->get('callback');
        $jsonData = [
            'code'    => 0,
            'message' => 'success',
            'data'    => $data
        ];

        if (!empty($callback)) {
            return response()->jsonp($callback, $jsonData);
        }
        return response()->json($jsonData);
    }
}
