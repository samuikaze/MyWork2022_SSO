<?php

namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\AccessToken;
use App\Repositories\Abstracts\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TokenRepository extends BaseRepository
{
    /**
     * 權杖有效日
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $authenticate_valid_date;

    public function name(): string
    {
        return '權杖';
    }

    public function __construct(AccessToken $access_token)
    {
        parent::__construct();
        $this->model = $access_token;

        $duration = (int) env('AUTHENTICATE_VALID_DURATION', 120);
        $this->authenticate_valid_date = now()->subMinutes($duration);
    }

    /**
     * 取得存取權杖資訊
     *
     * @param int $user_id 使用者 ID
     * @param int $access_token_id 存取權杖 ID
     * @param string $access_token 存取權杖
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function findAccessToken(int $user_id, int $access_token_id, string $access_token): Model
    {
        $access_token_record = $this->model
            ->select('access_tokens.*')
            ->where('access_tokens.user_id', $user_id)
            ->where('access_tokens.access_token_id', $access_token_id)
            ->where('access_tokens.access_token', $access_token)
            ->first();

        if (is_null($access_token_record)) {
            throw new EntityNotFoundException('找不到該存取權杖');
        }

        return $access_token_record;
    }

    /**
     * 找出有效期間內有效使用者的權杖
     *
     * @param int $user_id 使用者 ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getValidTokensByUserId(int $user_id): Collection
    {
        return $this->model
            ->select('access_tokens.*')
            ->where('access_tokens.user_id', $user_id)
            ->where('access_tokens.last_used_at', '<=', $this->authenticate_valid_date)
            ->orderBy('access_tokens.access_token_id', 'desc')
            ->get();
    }

    /**
     * 清除已過期的權杖
     *
     * @return void
     */
    public function GC(): void
    {
        $this->model
            ->where(function ($query) {
                $query->where('access_tokens.last_used_at', '<=', $this->authenticate_valid_date)
                    ->orWhere('access_tokens.last_used_at');
            })
            ->where('access_tokens.remember', 0)
            ->delete();
    }
}
