<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Helpers\AppHelpers;

/**
 * 解决跨域请求
 *
 * @author TianHaisen
 */
class EnableCrossRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        // 预发布和线上环境使用nginx配置跨域
        if (AppHelpers::isStagingEnv() || AppHelpers::isProductionEnv()) {
            return $response;
        }

        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN');
        $response->header('Access-Control-Expose-Headers', 'Authorization, authenticated');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
        $response->header('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
