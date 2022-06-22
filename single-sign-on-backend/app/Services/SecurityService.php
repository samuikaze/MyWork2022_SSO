<?php

namespace App\Services;

use App\Exceptions\EntityNotFoundException;
use App\Mail\ResetPassword;
use App\Repositories\PasswordRepository;
use App\Repositories\SystemVariableRepository;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class SecurityService
{
    /**
     * PasswordRepository
     *
     * @var \App\Repositories\PasswordRepository
     */
    protected $password_repository;

    /**
     * SystemVariableRepository
     *
     * @var \App\Repositories\SystemVariableRepository
     */
    protected $system_variable_repository;

    /**
     * TokenRepository
     *
     * @var \App\Repositories\TokenRepository
     */
    protected $token_repository;

    /**
     * UserRepository
     *
     * @var \App\Repositories\UserRepository
     */
    protected $user_repository;

    /**
     * 是否經由電子郵件重置密碼
     *
     * @var bool
     */
    protected $reset_pswd_thu_mail;

    /**
     * 建構方法
     *
     * @param \App\Repositories\PasswordRepository $password_repository
     * @param \App\Repositories\SystemVariableRepository $system_variable_repository
     * @param \App\Repositories\TokenRepository $token_repository
     * @param \App\Repositories\UserRepository $user_repository
     * @return void
     */
    public function __construct(
        PasswordRepository $password_repository,
        SystemVariableRepository $system_variable_repository,
        TokenRepository $token_repository,
        UserRepository $user_repository
    ) {
        $this->password_repository = $password_repository;
        $this->system_variable_repository = $system_variable_repository;
        $this->token_repository = $token_repository;
        $this->user_repository = $user_repository;

        $this->reset_pswd_thu_mail = env('RESET_PSWD_THROUGH_MAIL', true);
    }

    /**
     * 忘記密碼
     *
     * @param string $email
     * @return string|null
     */
    public function forgetPassword(string $email)
    {
        try {
            $user = $this->user_repository->findUserByAccountOrEmail('', $email);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException('輸入的電子郵件地址不正確');
        }

        $apply_date = now();

        $token = encrypt([
            'user_id' => $user->id,
            'apply_date' => $apply_date,
            'created_at' => $user->created_at,
        ]);

        $record = [
            'email' => $email,
            'token' => $token,
            'created_at' => $apply_date,
        ];
        $record = $this->password_repository->create($record);

        $token = $record->token;

        if ($this->reset_pswd_thu_mail) {
            $mail = new ResetPassword($record->token);
            Mail::to($email)->send($mail);

            return null;
        }

        return $token;
    }

    /**
     * 重設密碼
     *
     * @param string $email 電子郵件地址
     * @param string $token 權杖
     * @param string $password 新密碼
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        ['user_id' => $user_id] = decrypt($token);
        try {
            $user = $this->user_repository->findUserByAccountOrEmail('', $email);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException('無效的權杖');
        }

        if ($user->id != $user_id || $user->email != $email) {
            throw new InvalidArgumentException('無效的權杖');
        }

        try {
            $token = $this->password_repository->findApplyByEmailAndToken($email, $token);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException('無效的權杖');
        }

        $password = Hash::make($password);
        $this->user_repository->safeUpdateRecord($user_id, ['password' => $password]);

        $this->password_repository->deleteRecord($token->id);
    }

    /**
     * 取得重設密碼權杖資訊
     *
     * @param string $token 權杖
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getResetPasswordInformation(string $token): array
    {
        try {
            ['user_id' => $user_id] = decrypt($token);
        } catch (DecryptException $e) {
            throw new InvalidArgumentException('無效的權杖');
        }

        try {
            $user = $this->user_repository->find($user_id);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException('權杖資訊不完整');
        }

        return $user->only('id', 'email');
    }

    /**
     * 清理過期權杖
     *
     * @return void
     */
    public function invokeExpiredTokens()
    {
        $var_name = 'EXPIRED_TOKENS_INVOKE_TIME';
        $now = now()->format('Y-m-d H:i:s');
        try {
            $last_invoked = $this->system_variable_repository->getSystemVariable($var_name);
            $last_invoked = Carbon::parse($last_invoked->value, 'UTC');
            if ($last_invoked->diffInDays(now(), true) > 0) {
                $this->token_repository->GC();
                $this->system_variable_repository->setSystemVariable($var_name, $now);
            }
        } catch (EntityNotFoundException $e) {
            $this->token_repository->GC();
            $this->system_variable_repository->create([
                'name' => $var_name,
                'value' => $now,
            ]);
        }
    }
}
