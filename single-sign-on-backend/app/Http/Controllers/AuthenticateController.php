<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityNotFoundException;
use App\Services\AuthenticateService;
use App\Services\SecurityService;
use App\Services\ValidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class AuthenticateController extends Controller
{
    /**
     * AuthenticateService
     *
     * @var \App\Services\AuthenticateService
     */
    protected $authenticate_service;

    /**
     * SecurityService
     *
     * @var \App\Services\SecurityService
     */
    protected $security_service;

    /**
     * ValidateService
     *
     * @var \App\Services\ValidateService
     */
    protected $validate_service;

    /**
     * 建構方法
     *
     * @param \App\Services\AuthenticateService $authenticate_service
     * @param \App\Services\SecurityService $security_service
     * @param \App\Services\ValidateService $validate_service
     * @return void
     */
    public function __construct(
        AuthenticateService $authenticate_service,
        SecurityService $security_service,
        ValidateService $validate_service
    ) {
        $this->authenticate_service = $authenticate_service;
        $this->security_service = $security_service;
        $this->validate_service = $validate_service;
    }

    /**
     * 註冊
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signUp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->response(
                $validator->errors(),
                null,
                self::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->authenticate_service->signUp(
                $request->input('account'),
                $request->input('password'),
                $request->input('email'),
                $request->input('name'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->response(
                $e->getMessage(),
                null,
                self::HTTP_BAD_REQUEST
            );
        }

        $signin_request = new Request();
        $signin_request->merge([
            'account' => $request->input('account'),
            'password' => $request->input('password'),
            'remember' => false,
        ]);

        $response = $this->signIn($signin_request);

        return $response;
    }

    /**
     * 登入
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->response(
                '登入失敗',
                null,
                self::HTTP_UNAUTHORIZED
            );
        }

        $remember = (int) $request->input('remember');
        $remember = ($remember == 1) ? 1 : 0;

        try {
            [$user, $token] = $this->authenticate_service->userAuthentication(
                $request->input('account'),
                $request->input('password'),
                $remember
            );
        } catch (InvalidArgumentException $e) {
            return $this->response(
                $e->getMessage(),
                null,
                self::HTTP_UNAUTHORIZED
            );
        }

        $response = [
            'user' => $user,
            'tokenType' => 'Bearer',
            'accessToken' => $token,
        ];

        return $this->response(null, $response);
    }

    /**
     * 登出
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signOut(Request $request): JsonResponse
    {
        $bearer_token = $request->bearerToken();

        $this->authenticate_service->signOut($bearer_token);

        return $this->response();
    }

    /**
     * 忘記密碼
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->response(
                '輸入的電子郵件信箱不正確',
                null,
                self::HTTP_BAD_REQUEST
            );
        }

        try {
            $token = $this->security_service->forgetPassword($request->input('email'));
        } catch (InvalidArgumentException $e) {
            return $this->response($e->getMessage(), null, self::HTTP_BAD_REQUEST);
        }

        if (! is_null($token)) {
            return $this->response(null, $token);
        }

        return $this->response();
    }

    /**
     * 重設密碼
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmation'],
        ]);

        if ($validator->fails()) {
            return $this->response(
                '重設密碼失敗，請再試一次',
                null,
                self::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->security_service->resetPassword(
                $request->input('email'),
                $request->input('token'),
                $request->input('password')
            );
        } catch (InvalidArgumentException $e) {
            return $this->response(
                $e->getMessage(),
                null,
                self::HTTP_BAD_REQUEST
            );
        }

        return $this->response();
    }

    /**
     * 外部驗證
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function externalAuthorization(Request $request): JsonResponse
    {
        $access_token = $request->bearerToken();
        $update_last_used_date = true;

        if (is_null($access_token)) {
            return $this->response('驗證失敗', null, self::HTTP_UNAUTHORIZED);
        }

        try {
            $user = $this->authenticate_service->verifyJWToken($access_token, $update_last_used_date);
        } catch (InvalidArgumentException $e) {
            return $this->response($e->getMessage(), null, self::HTTP_UNAUTHORIZED);
        }

        return $this->response(null, $user);
    }

    /**
     * 內部系統驗證
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function internalSystemAuthorization(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_token' => ['required', 'string'],
            'system' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->response('驗證失敗', null, self::HTTP_UNAUTHORIZED);
        }

        try {
            $this->validate_service->isSystemExists($request->input('system'));
            $user = $this->authenticate_service->verifyJWToken($request->input('access_token'));
        } catch (EntityNotFoundException | InvalidArgumentException $e) {
            return $this->response($e->getMessage(), null, self::HTTP_UNAUTHORIZED);
        }

        $data = encrypt($user['id']);

        return $this->response(null, $data);
    }

    /**
     * 取得重設密碼權杖資訊
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResetPasswordInformation(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (is_null($token)) {
            return $this->response(
                '請確實給出權杖',
                null,
                self::HTTP_UNAUTHORIZED
            );
        }

        try {
            $info = $this->security_service->getResetPasswordInformation($token);
        } catch (InvalidArgumentException $e) {
            return $this->response(
                $e->getMessage(),
                null,
                self::HTTP_UNAUTHORIZED
            );
        }

        return $this->response(null, $info);
    }
}
