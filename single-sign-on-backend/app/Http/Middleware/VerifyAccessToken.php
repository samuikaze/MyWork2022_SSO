<?php

namespace App\Http\Middleware;

use App\Traits\ResponseFormatter;
use App\Services\AuthenticateService;
use Closure;
use Exception;
use Illuminate\Http\Request;

class VerifyAccessToken
{
    use ResponseFormatter;

    /**
     * AuthenticateService
     *
     * @var \App\Services\AuthenticateService
     */
    protected $authenticate_service;

    /**
     * 建構方法
     *
     * @param \App\Services\AuthenticateService $authenticate_service
     * @return void
     */
    public function __construct(AuthenticateService $authenticate_service)
    {
        $this->authenticate_service = $authenticate_service;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $bearer_token = $request->bearerToken();

        try {
            $this->authenticate_service->verifyJWToken($bearer_token);
        } catch (Exception $e) {
            return $this->response('驗證失敗', null, 401);
        }

        return $next($request);
    }
}
