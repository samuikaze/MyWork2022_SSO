<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\AuthenticateController;
use Tests\TestCase;

class AuthenticateControllerTest extends TestCase
{
    /**
     * AuthenticateController
     *
     * @var \App\Http\Controllers\AuthenticateController
     */
    protected $controller;

    /**
     * 測試前
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->controller = app(AuthenticateController::class);
    }
}
