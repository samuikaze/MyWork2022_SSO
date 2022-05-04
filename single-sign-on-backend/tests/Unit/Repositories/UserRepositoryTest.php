<?php

namespace Tests\Unit\Repositories;

use App\Repositories\UserRepository;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    /**
     * UserRepository
     *
     * @var \App\Repositories\UserRepository
     */
    protected $repository;

    /**
     * 測試前
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }
}
