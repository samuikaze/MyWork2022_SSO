<?php

namespace Tests\Unit\Repositories;

use App\Repositories\TokenRepository;
use Tests\TestCase;

class TokenRepositoryTest extends TestCase
{
    /**
     * UserRepository
     *
     * @var \App\Repositories\TokenRepository
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

        $this->repository = app(TokenRepository::class);
    }
}
