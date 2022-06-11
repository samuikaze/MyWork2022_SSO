<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandleCorsHeader
{
    /**
     * 允許的網域
     *
     * @var array<string>
     */
    protected $allow_origins;

    /**
     * 允許的存取方法
     *
     * @var array<string>
     */
    protected $allow_methods;

    /**
     * 允許的標頭
     *
     * @var array<string>
     */
    protected $allow_headers;

    /**
     * 最大存活時間
     *
     * @var int
     */
    protected $max_age;

    /**
     * 是否支援證書
     *
     * @var string
     */
    protected $support_credentials;

    /**
     * 建構方法
     *
     * @return void
     */
    public function __construct()
    {
        $this->allow_origins = config('cors.allowed_origins');
        $this->allow_methods = config('cors.allowed_methods');
        $this->allow_headers = config('cors.allowed_headers');
        $this->max_age = config('cors.max_age');
        $this->support_credentials = config('cors.support_credentials');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $next($request);

        if (! is_null($response)) {
            $response = $response->header('Cache-Control', 'max-age='.$this->max_age);

            $origin = $request->server('HTTP_ORIGIN');

            if (in_array($origin, $this->allow_origins) || in_array('*', $this->allow_origins)) {
                $response = $response
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Headers', implode(',', $this->allow_headers))
                    ->header('Access-Control-Allow-Methods', implode(',', $this->allow_methods))
                    ->header('Access-Control-Allow-Credentials', $this->support_credentials);
            }
        }

        return $response;
    }
}
