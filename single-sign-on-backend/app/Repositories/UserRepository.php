<?php

namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\User;
use App\Repositories\Abstracts\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository
{
    public function name(): string
    {
        return '使用者';
    }

    public function __construct(User $user)
    {
        parent::__construct();

        $this->model = $user;
    }

    /**
     * 以帳號找出使用者
     *
     * @param string $account 帳號
     * @return \App\Models\User|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Contracts\Queue\EntityNotFoundException
     */
    public function findUserByAccount(string $account): Model
    {
        $user = $this->model
            ->where('account', $account)
            ->first();

        if (is_null($user)) {
            throw new EntityNotFoundException('找不到該 '.$this->model_name);
        }

        return $user;
    }

    /**
     * 以電子郵件地址或帳號找出資料
     *
     * @param string $account 帳號
     * @param string $email 電子郵件地址
     * @return \App\Models\User|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Contracts\Queue\EntityNotFoundException
     */
    public function findUserByAccountOrEmail(string $account, string $email): Model
    {
        $user = $this->model
            ->where('account', $account)
            ->orWhere('email', $email)
            ->first();

        if (is_null($user)) {
            throw new EntityNotFoundException('找不到該 '.$this->model_name);
        }

        return $user;
    }
}
