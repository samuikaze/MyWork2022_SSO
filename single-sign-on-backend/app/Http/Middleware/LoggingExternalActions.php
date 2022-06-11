<?php

namespace App\Http\Middleware;

use App\Repositories\LoggingRepository;
use Closure;
use Illuminate\Http\Request;

class LoggingExternalActions
{
    /**
     * 來源名稱
     *
     * @var int
     */
    protected const SOURCE = 1;

    /**
     * LoggingRepository
     *
     * @var \App\Repositories\LoggingRepository
     */
    protected $logging_repository;

    /**
     * 建構方法
     *
     * @param \App\Repositories\LoggingRepository $logging_repository
     * @return void
     */
    public function __construct(LoggingRepository $logging_repository)
    {
        $this->logging_repository = $logging_repository;
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
        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $next($request);

        if (! is_null($response)) {
            [
                'status' => $status,
                'data' => $data
            ] = $response->getOriginalContent();
        } else {
            $status = 0;
            $data = null;
        }

        $method = $request->method();
        $ip = $request->ip();
        $uri = $request->path();
        $request_payloads = $request->all();
        if (count($request_payloads) === 0) {
            $request_payloads = null;
        } else {
            $request_payloads = json_encode($request_payloads, JSON_UNESCAPED_UNICODE);
        }

        if (is_array($data) && array_key_exists('id', $data)) {
            $user_id = (int) $data['id'];
        } else {
            $user_id = null;
        }
        unset($data);

        $this->logging($uri, $method, $status, $request_payloads, $user_id, $ip);

        return $response;
    }

    /**
     * 寫入日誌
     *
     * @param string $uri URI
     * @param string $method 存取方法
     * @param int $status HTTP 狀態
     * @param string|null $request_payloads 請求酬載
     * @param int|null $user_id 使用者 ID
     * @param string|null $ip 來源 IP
     * @return void
     */
    protected function logging(string $uri, string $method, int $status, string $request_payloads = null, int $user_id = null, string $ip = null): void
    {
        $this->logging_repository->create([
            'uri' => $uri,
            'method' => $method,
            'user_id' => $user_id,
            'source' => self::SOURCE,
            'access_ip' => $ip,
            'http_status' => $status,
            'request_payloads' => $request_payloads,
        ]);
    }
}
