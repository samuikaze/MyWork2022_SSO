<?php

namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\PasswordReset;
use App\Repositories\Abstracts\BaseRepository;

class PasswordRepository extends BaseRepository
{
    /**
     * PasswordReset model
     *
     * @var \Illuminate\Database\Eloquent\Model|\App\Models\PasswordReset
     */
    protected $password_reset;

    public function name(): string
    {
        return '忘記密碼申請';
    }

    public function __construct(PasswordReset $password_reset) {
        parent::__construct();

        $this->model = $password_reset;
    }

    /**
     * 以電子郵件地址、權杖找出重置密碼的申請
     *
     * @param string $email 電子郵件地址
     * @param string $token 權杖
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function findApplyByEmailAndToken(string $email, string $token)
    {
        $apply = $this->model
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (is_null($apply)) {
            throw new EntityNotFoundException('找不到該'.$this->name);
        }

        return $apply;
    }
}
