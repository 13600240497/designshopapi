<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Foundation\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helpers\AppHelpers;

/**
 * 全局异常处理
 *
 * @author TianHaisen
 */
class GlobalExceptionHandler extends Handler
{
    /** @var array 不报告异常的异常类列表 */
    protected $dontReport = [

    ];

    /** @var array A list of the inputs that are never flashed for validation exceptions. */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * 报告异常
     *
     * @param Exception $exception 异常
     * @return void
     */
    public function report(Exception $exception)
    {
        // 非正式环境 全部记录
        if (AppHelpers::isProductionEnv() && $this->shouldntReport($exception)) {
            return;
        }

        // 记录日志
        if (!($exception instanceof AppException) || $exception->isLogInReport()) {
//            ges_error_log(__CLASS__, '运行异常 %s', $exception);
        }

        // 告警(不包含404错误)
        if (!$exception instanceof NotFoundHttpException) {
            app('rms')->observeException($exception);
        }
    }

    /**
     * Http 异常渲染返回
     *
     * @param  Request  $request
     * @param Exception $exception
     * @return JsonResponse
     */
    public function render($request, Exception $exception)
    {
        if (method_exists($exception, 'render') && $response = $exception->render($request)) {
            return Router::toResponse($request, $response);
        } else {
            return $this->apiJsonResponse($request, $exception);
        }
    }

    /**
     * API 接口json错误返回
     *
     * @param  Request  $request
     * @param Exception $exception
     * @return JsonResponse
     */
    protected function apiJsonResponse($request, Exception $exception)
    {
        $debug  = config('app.debug');
        $data   = method_exists($exception, 'getData') ? $exception->getData() : [];
        $requestUri = $request->getRequestUri();

        // 非正式环境 debug模式开启调试参数
        if ($debug && !AppHelpers::isProductionEnv()) {
            $data['innerStack'] = $exception->getTraceAsString();
            $data['innerMsg']   = $exception->getMessage();
        }

        $jsonData = null;
        if (Str::startsWith($requestUri, '/api/')) { // APP 接口
            $jsonData = [
                'statusCode'    => 500,
                'msg'           => 'Internal Server Error'
            ];
        } else {
            $jsonData = [
                'code'    => 500,
                'message' => 'Internal Server Error',
            ];
        }
        $jsonData['data'] = $data;

        $callback = request()->get('callback');
        if (!empty($callback)) {
            return response()->jsonp($callback, $jsonData);
        }
        return response()->json($jsonData);
    }
}
