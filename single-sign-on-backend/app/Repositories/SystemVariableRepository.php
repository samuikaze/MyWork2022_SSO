<?php

namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\SystemVariable;
use App\Repositories\Abstracts\BaseRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class SystemVariableRepository extends BaseRepository
{
    public function name(): string
    {
        return '系統變數';
    }

    public function __construct(SystemVariable $system_variable)
    {
        parent::__construct();

        $this->model = $system_variable;
    }

    /**
     * 取得系統變數
     *
     * @param string $name 系統變數名稱
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function getSystemVariable(string $name)
    {
        $result = $this->model
            ->select('system_variables.name', 'system_variables.value')
            ->where('system_variables.name', $name)
            ->first();

        if (is_null($result)) {
            throw new EntityNotFoundException('找不到該'.$this->model_name);
        }

        return $result;
    }

    /**
     * 更新系統變數值
     *
     * @param string $name 系統變數名稱
     * @param string $value 新值
     * @return void
     */
    public function setSystemVariable(string $name, string $value)
    {
        DB::beginTransaction();

        try {
            $variable = $this->model
                ->select('system_variables.*')
                ->where('system_variables.name', $name)
                ->lockForUpdate()
                ->first();

            if (is_null($variable)) {
                throw new EntityNotFoundException('找不到該'.$this->model_name);
            }

            $variable->value = $value;
            $variable->save();
        } catch (Exception $e) {
            report($e);

            DB::rollBack();

            throw $e;
        }

        DB::commit();
    }
}
