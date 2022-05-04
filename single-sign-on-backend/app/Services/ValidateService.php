<?php

namespace App\Services;

use App\Repositories\SystemRepository;

class ValidateService
{
    /**
     * SystemRepository
     *
     * @var \App\Repositories\SystemRepository
     */
    protected $system_repository;

    /**
     * 建構方法
     *
     * @param \App\Repositories\SystemRepository $system_repository
     * @return void
     */
    public function __construct(SystemRepository $system_repository)
    {
        $this->system_repository = $system_repository;
    }

    /**
     * 檢查系統是否存在
     *
     * @param string $system_name 系統名稱
     * @return bool
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function isSystemExists(string $system_name)
    {
        return $this->system_repository->findValidRegisteredSystem($system_name);
    }
}
