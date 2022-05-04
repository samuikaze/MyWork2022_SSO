<?php

namespace App\Http\Middleware;

use App\Repositories\LoggingRepository;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;

class LoggingInternalActions
{
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

        ['data' => $data] = $response->getOriginalContent();
        $status = $response->getStatusCode();
        $uri = $request->path();
        $method = $request->method();
        $ip = $request->ip();
        $source = $request->input('systems') ?? null;

        $request_payloads = ['system' => ($source ?? 'unknown')];
        $request_payloads = json_encode($request_payloads, JSON_UNESCAPED_UNICODE);

        $user_id = null;
        if (! is_null($data)) {
            try {
                $data = decrypt($data);
                $user_id = (int) $data;
                unset($data);
            } catch (DecryptException $e) {
                //
            }
        }

        $this->logging($uri, $method, $status, $source, $request_payloads, $user_id, $ip);

        return $response;
    }

    /**
     * 寫入日誌
     *
     * @param string $uri URI
     * @param string $method 存取方法
     * @param int $status HTTP 狀態
     * @param int|null $source 來源系統
     * @param string|null $request_payloads 請求酬載
     * @param int|null $user_id 使用者 ID
     * @param string|null $ip 來源 IP
     * @return void
     */
    protected function logging(
        string $uri,
        string $method,
        int $status,
        int $source = null,
        string $request_payloads = null,
        int $user_id = null,
        string $ip = null
    ): void {
        $this->logging_repository->create([
            'uri' => $uri,
            'method' => $method,
            'user_id' => $user_id,
            'source' => $source,
            'access_ip' => $ip,
            'http_status' => $status,
            'request_payloads' => $request_payloads,
        ]);
    }
}
