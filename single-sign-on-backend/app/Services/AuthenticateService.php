<?php

namespace App\Services;

use App\Exceptions\EntityNotFoundException;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AuthenticateService
{
    /**
     * TokenRepository
     *
     * @var \App\Repositories\TokenRepository
     */
    protected $token_repository;

    /**
     * UserRepositoory
     *
     * @var \App\Repositories\UserRepository
     */
    protected $user_repository;

    /**
     * 建構方法
     *
     * @param \App\Repositories\TokenRepository $token_repository
     * @param \App\Repositories\UserRepository $user_repository
     * @return void
     */
    public function __construct(
        TokenRepository $token_repository,
        UserRepository $user_repository
    ) {
        $this->token_repository = $token_repository;
        $this->user_repository = $user_repository;
    }

    /**
     * 註冊
     *
     * @param string $account 帳號
     * @param string $password 密碼
     * @param string $email 電子郵件地址
     * @param string $name 暱稱
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function signUp(string $account, string $password, string $email, string $name): void
    {
        $duplicate = $this->checkIfUserIsExists($account, $email);
        if ($duplicate) {
            throw new InvalidArgumentException('該帳號或電子郵件地址已被使用過');
        }

        $user = [
            'account' => $account,
            'password' => Hash::make($password),
            'email' => $email,
            'name' => $name,
        ];

        $this->user_repository->create($user);
    }

    /**
     * 登入
     *
     * @param string $account 帳號或電子郵件地址
     * @param string $password 密碼
     * @param int $remember 記憶登入狀態
     * @return \Illuminate\Database\Eloquent\Model[]|string[] [$user, $token] 返回帳號資料與權杖
     *
     * @throws \InvalidArgumentException
     */
    public function userAuthentication(string $account, string $password, int $remember): array
    {
        try {
            $user = $this->user_repository->findUserByAccountOrEmail($account, $account);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException('帳號或密碼錯誤');
        }

        $authenticate = Hash::check($password, $user->password);
        if (! $authenticate) {
            throw new InvalidArgumentException('帳號或密碼錯誤');
        }

        $tokens = $this->token_repository->getValidTokensByUserId($user->id);
        if ($tokens->count() == 0) {
            $access_token_id = 1;
        } else {
            $access_token_id = ((int) $tokens->first()->access_token_id) + 1;
        }

        $access_token = Str::random(64);

        $token_payloads = [
            'user_id' => $user->id,
            'access_token_id' => $access_token_id,
            'access_token' => $access_token,
            'remember' => $remember,
        ];
        $this->token_repository->create($token_payloads);

        $access_token = [
            'user_account' => $user->account,
            'session' => encrypt([
                'access_token_id' => $access_token_id,
                'access_token' => $access_token,
            ]),
            'signed_in_at' => now(),
        ];
        $access_token = $this->generateAccessToken($access_token);

        return [$user, $access_token];
    }

    /**
     * 登出
     *
     * @param string $bearer_token 存取權杖
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function signOut(string $bearer_token): void
    {

        [
            'user_account' => $account,
            'session' => $session,
            'signed_in_at' => $first_signin
        ] = $this->decodeAccessToken($bearer_token);

        [
            'access_token_id' => $access_token_id,
            'access_token' => $access_token,
        ] = $session;

        unset($first_signin, $session);

        try {
            $user = $this->user_repository->findUserByAccount($account);
            if (is_null($user)) {
                throw new InvalidArgumentException('無此帳號');
            }

            $access_token = $this->token_repository->findAccessToken($user->id, $access_token_id, $access_token);
            if (is_null($access_token)) {
                throw new InvalidArgumentException('尚未登入');
            }

            $this->token_repository->deleteRecord($access_token->id);
        } catch (EntityNotFoundException $e) {
            // 登出時遇到已知錯誤不拋錯
            // 因為這種時候通常是權杖過期
        }
    }

    /**
     * 檢查帳號或電子郵件地址是否已經存在
     *
     * @param string $account 帳號
     * @param string $email 電子郵件地址
     * @return bool
     */
    protected function checkIfUserIsExists(string $account, string $email): bool
    {
        try {
            $this->user_repository->findUserByAccountOrEmail($account, $email);
        } catch (EntityNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * 檢查存取權杖有效性
     *
     * @param string|null $jwt_token 存取權杖
     * @param bool $update_used_date [false] 是否更新最後使用時間
     * @return array<string,int|string>
     *
     * @throws \InvalidArgumentException
     */
    public function verifyJWToken(string $jwt_token = null, bool $update_used_date = false): array
    {
        if (is_null($jwt_token)) {
            throw new InvalidArgumentException('驗證失敗');
        }

        [
            'user_account' => $account,
            'session' => $session,
            'signed_in_at' => $first_signin
        ] = $this->decodeAccessToken($jwt_token);

        [
            'access_token_id' => $access_token_id,
            'access_token' => $access_token,
        ] = $session;

        unset($session, $first_signin);

        $user = $this->user_repository->findUserByAccount($account);
        if (is_null($user)) {
            throw new InvalidArgumentException('驗證失敗');
        }

        $access_token = $this->token_repository->findAccessToken($user->id, $access_token_id, $access_token);
        if (is_null($access_token)) {
            throw new InvalidArgumentException('驗證失敗');
        }

        if ($update_used_date) {
            $this->token_repository->safeUpdateRecord($access_token->id, ['last_used_at' => now()]);
        }

        return $user->only('id', 'name', 'account', 'email', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at');
    }

    /**
     * 產生存取權杖
     *
     * @param array<string,mixed> $payloads
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function generateAccessToken(array $payloads): string
    {
        $headers = $this->generateJWTHeader();
        $payloads = $this->generateJWTPayloads($payloads);
        $secret = $this->generateJWTSecret($headers, $payloads);

        $access_token = $headers.'.'.$payloads.'.'.$secret;
        $access_token = base64_encode($access_token);

        return $access_token;
    }

    /**
     * 驗證並取得存取權杖資訊
     *
     * @param string $jwt_token 存取權杖
     * @return array<string,mixed> [$account, $session, $first_signin]
     *
     * @throws \InvalidArgumentException
     */
    protected function decodeAccessToken(string $jwt_token): array
    {
        $jwt_token = base64_decode($jwt_token);
        $jwt_token = explode('.', $jwt_token);
        if (count($jwt_token) != 3) {
            throw new InvalidArgumentException('驗證失敗');
        }
        [$headers, $payloads, $secret] = $jwt_token;
        unset($jwt_token);

        $calculated_secret = $this->generateJWTSecret($headers, $payloads);
        if ($calculated_secret !== $secret) {
            throw new InvalidArgumentException('驗證失敗');
        }
        unset($calculated_secret, $secret);

        $headers = base64_url_decode($headers);
        $payloads = base64_url_decode($payloads);

        if ($headers === false || $payloads === false) {
            throw new InvalidArgumentException('驗證失敗');
        }

        $payloads = json_decode($payloads, true);

        try {
            $payloads['session'] = decrypt($payloads['session']);
        } catch (DecryptException $e) {
            throw new InvalidArgumentException('驗證失敗');
        }

        return $payloads;
    }

    /**
     * 產生 JWT 標頭
     *
     * @param bool $want_original 是否取得原始資料
     * @return array<string,string>|string
     *
     * @throws \InvalidArgumentException
     */
    protected function generateJWTHeader(bool $want_original = false)
    {
        $algorithm = env('JWT_SECRET_JWT_ALGORITHM');
        if (is_null($algorithm)) {
            throw new InvalidArgumentException('請先設定系統 JWT Secret 加密演算法');
        }

        $headers = [
            'alg' => $algorithm,
            'typ' => 'JWT',
        ];

        if ($want_original) {
            return $headers;
        }

        $headers = json_encode($headers);
        $headers = base64_url_encode($headers);

        return $headers;
    }

    /**
     * 產生 JWT 資料
     *
     * @param array $payloads
     * @param bool $want_original 是否取得原始資料
     * @return array<string,string>|string
     */
    protected function generateJWTPayloads(array $payloads, bool $want_original = false)
    {
        $fixed_payloads = [];
        $payloads = array_merge($payloads, $fixed_payloads);

        if ($want_original) {
            return $payloads;
        }

        $payloads = json_encode($payloads);
        $payloads = base64_url_encode($payloads);

        return $payloads;
    }

    /**
     * 取得 JWT 權杖的秘密 (Secret)
     *
     * @param string $header
     * @param string $payloads
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function generateJWTSecret(string $header, string $payloads): string
    {
        /** @var string $secret */
        $secret = env('JWT_SECRET');
        if (is_null($secret)) {
            throw new InvalidArgumentException('請先設定系統 JWT Secret');
        }

        /** @var string $algorithm */
        $algorithm = env('JWT_SECRET_ALGORITHM');
        if (is_null($algorithm)) {
            throw new InvalidArgumentException('請先設定系統 JWT Secret 演算法');
        }

        $body = $header.'.'.$payloads;

        return hash_hmac(strtolower($algorithm), $body, $secret, false);
    }
}
