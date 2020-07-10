<?php
namespace App\Http\Controllers\App;

use App\Http\Controllers\AbstractController;
use Illuminate\Http\JsonResponse;

/**
 * App原生接口抽象类
 *
 * @author TianHaisen
 */
abstract class AbstractAppController extends AbstractController
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->middleware('app-header-verification');
        parent::__construct();
    }

    /**
     * API接口层json成功返回
     *
     * @param array $data 返回数据
     * @return mixed
     */
    protected function apiJsonSuccess($data)
    {
        if (is_array($data) && isset($data['result'])) {
            $jsonData = $data;
        } else {
            $jsonData['result'] = $data;
        }

        $jsonData['statusCode'] = 200;
        $jsonData['msg'] = 'Success';
        return response()->json($jsonData);
    }

    /**
     * API接口层json失败返回
     *
     * @param string $message
     * @return mixed
     */
    protected function apiJsonFail($message)
    {
        $jsonData = [
            'statusCode' => 500,
            'msg' => $message
        ];
        return response()->json($jsonData);
    }
}
