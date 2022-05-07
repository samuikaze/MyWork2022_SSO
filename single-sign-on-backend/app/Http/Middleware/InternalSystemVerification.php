<?php

namespace App\Http\Middleware;

use App\Exceptions\DecryptException;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidPayloadException;
use App\Services\ValidateService;
use App\Traits\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InternalSystemVerification
{
    use ResponseFormatter;

    /**
     * ValidateService
     *
     * @var \App\Services\ValidateService
     */
    protected $validate_service;

    /**
     * 加解密演算法
     *
     * @var string
     */
    protected $encrypt_algo;

    /**
     * 加解密金鑰
     *
     * @var string
     */
    protected $encrypt_key;

    /**
     * IV
     *
     * @var string
     */
    protected $iv;

    /**
     * 建構方法
     *
     * @param \App\Services\ValidateService $validate_service
     * @return void
     */
    public function __construct(ValidateService $validate_service)
    {
        $this->validate_service = $validate_service;
        $this->encrypt_algo = env('ENCRYPT_ALGORITHM', 'aes-256-cbc');
        $this->encrypt_key = env('ENCRYPT_KEY');
        if (strlen($this->encrypt_key) === 0) {
            throw new InvalidArgumentException('請先設定各系統間的加解密金鑰');
        }
        $this->encrypt_key = password_hash($this->encrypt_key, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->iv = '';
        for ($i = 0; $i < 16; $i++) {
            $this->iv .= chr(0x0);
        }
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
        $token = $request->bearerToken();

        try {
            [$system_id, $system_name] = $this->decryptPayloads($token);
            $system = $this->validate_service->isSystemExists($system_name);
        } catch (DecryptException | InvalidPayloadException | EntityNotFoundException $e) {
            return $this->response('驗證失敗', null, 401);
        }

        if ($system_id != $system->id) {
            return $this->response('驗證失敗', null, 401);
        }

        $now = now()->toIso8601String();
        $header = 'Bearer '.$this->encryptPayloads('2|single_sign_on|'.$now);
        $response->header('Authorization', $header);

        return $response;
    }

    /**
     * 加密
     *
     * @param mixed $raw_data
     * @return string
     *
     * @see https://gist.github.com/Oranzh/2520823f9d1cea603e60b8e8f3fe1d36#file-with_bcrypt_password_hash-md
     */
    protected function encryptPayloads($raw_data)
    {
        $encrypted = $raw_data;
        if (! is_string($encrypted)) {
            $encrypted = json_encode($encrypted, JSON_UNESCAPED_UNICODE);
        }

        $encrypted = openssl_encrypt(
            $encrypted,
            $this->encrypt_algo,
            $this->encrypt_key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
        $encrypted = base64_encode($encrypted);

        return $encrypted;
    }

    /**
     * 解密
     *
     * @param string $encrypted
     * @return mixed
     *
     * @throws \App\Exceptions\DecryptException
     * @throws \App\Exceptions\InvalidPayloadException
     *
     * @see https://gist.github.com/Oranzh/2520823f9d1cea603e60b8e8f3fe1d36#file-with_bcrypt_password_hash-md
     */
    protected function decryptPayloads(string $encrypted): mixed
    {
        // 解密
        $decrypted = base64_decode($encrypted);
        if ($decrypted === false) {
            throw new DecryptException('資料解密失敗');
        }

        $decrypted = openssl_decrypt(
            $decrypted,
            $this->encrypt_algo,
            $this->encrypt_key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
        if ($decrypted === false) {
            throw new DecryptException('資料解密失敗');
        }

        $decrypted = explode('|', $decrypted);
        if (count($decrypted) !== 3) {
            throw new InvalidPayloadException('資料結構不正確');
        }

        return $decrypted;
    }
}
