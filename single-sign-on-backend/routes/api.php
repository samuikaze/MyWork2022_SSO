<?php

use App\Http\Controllers\AuthenticateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 外部 API
Route::group(['prefix' => 'v1', 'middleware' => ['external_logging']], function () {
    // 註冊
    Route::post('/user/signup', [AuthenticateController::class, 'signUp']);
    // 登入
    Route::post('/user/signin', [AuthenticateController::class, 'signIn']);
    // 需要驗證的 API
    Route::group(['middleware' => ['auth_api']], function () {
        // 驗證登入狀態，並取得帳號資訊
        Route::get('/user', [AuthenticateController::class, 'externalAuthorization']);
        // 登出
        Route::post('/user/signout', [AuthenticateController::class, 'signOut']);
    });
    // 忘記密碼
    Route::post('/forget/password', [AuthenticateController::class, 'forgetPassword']);
    // 取得重設密碼權杖資訊
    Route::get('/reset/password/token', [AuthenticateController::class, 'getResetPasswordInformation']);
    // 重設密碼
    Route::post('/reset/password', [AuthenticateController::class, 'resetPassword']);
});

// 內部 API
Route::group(['prefix' => 'v1/internal', 'middleware' => ['internal_logging', 'internal.verify']], function () {
    Route::get('/user', [AuthenticateController::class, 'internalSystemAuthorization']);
});
