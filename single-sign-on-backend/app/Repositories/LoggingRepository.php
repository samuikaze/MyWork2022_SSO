<?php

namespace App\Repositories;

use App\Models\ActionLog;
use App\Repositories\Abstracts\BaseRepository;

class LoggingRepository extends BaseRepository
{
    public function name(): string
    {
        return '日誌';
    }

    public function __construct(ActionLog $action_log)
    {
        parent::__construct();

        $this->model = $action_log;
    }
}
