<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * APP接口http头验证
 *
 * @author TianHaisen
 */
class AppHttpHeaderVerification
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
        $accept = $request->header('Accept');
        if ('application/vnd.GESHOP.v1+json' !== $accept) {
            throw new NotFoundHttpException('Page not found');
        }

        return $next($request);
    }
}
