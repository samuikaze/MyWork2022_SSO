<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityNotFoundException;
use App\Services\AuthenticateService;
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
     * ValidateService
     *
     * @var \App\Services\ValidateService
     */
    protected $validate_service;

    /**
     * 建構方法
     *
     * @param \App\Services\AuthenticateService $authenticate_service
     * @param \App\Services\ValidateService $validate_service
     * @return void
     */
    public function __construct(
        AuthenticateService $authenticate_service,
        ValidateService $validate_service
    ) {
        $this->authenticate_service = $authenticate_service;
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
                '請檢查所有必填欄位是否皆已填畢',
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

        $request = new Request([
            'account' => $request->input('account'),
            'password' => $request->input('password'),
            'remember' => false,
        ]);

        return $this->signIn($request);
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
}
