<?php

namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\RegisteredSystem;
use App\Repositories\Abstracts\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class SystemRepository extends BaseRepository
{
    public function name(): string
    {
        return '系統';
    }

    public function __construct(RegisteredSystem $registered_system)
    {
        parent::__construct();

        $this->model = $registered_system;
    }

    /**
     * 找出指定的系統
     *
     * @param string $system_name
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function findValidRegisteredSystem(string $system_name): Model
    {
        $system = $this->model
            ->where('registered_systems.valid', 1)
            ->where('registered_systems.name', $system_name)
            ->first();

        if (is_null($system)) {
            throw new EntityNotFoundException('找不到該系統或該系統目前不可使用');
        }

        return $system;
    }
}
