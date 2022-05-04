<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthenticateService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticateServiceTest extends TestCase
{
    /**
     * AuthenticateService
     *
     * @var \App\Services\AuthenticateService
     */
    protected $service;

    /**
     * 測試前
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthenticateService::class);
    }

    /**
     * 測試註冊功能
     *
     * @return void
     */
    public function testSignUpTest(): void
    {
        $account = 'TestUser';
        $password = 'TestUserPassword';
        $email = 'testuser@test.com';
        $name = 'testUser';

        $this->service->signUp($account, $password, $email, $name);

        $verify = User::where('account', $account)->first();
        $this->assertNotNull($verify);
        $this->assertEquals($account, $verify->account);
        $this->assertTrue(Hash::check($password, $verify->password));
        $this->assertEquals($email, $verify->email);
        $this->assertEquals($name, $verify->name);
    }

    /**
     * 測試登入功能
     *
     * @return void
     */
    public function testUserAuthenticationTest(): void
    {
        //
    }
}
