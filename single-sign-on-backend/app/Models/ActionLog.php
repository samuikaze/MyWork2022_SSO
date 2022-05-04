<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    use HasFactory;

    /**
     * 讀取的表格名稱
     *
     * @var string
     */
    protected $table = 'action_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uri',
        'method',
        'user_id',
        'access_ip',
        'http_status',
        'request_payloads',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
