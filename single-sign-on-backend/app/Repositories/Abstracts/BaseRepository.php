<?php

namespace App\Repositories\Abstracts;

use App\Exceptions\EntityNotFoundException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    /**
     * Model
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Model 名稱
     *
     * @var string
     */
    protected $model_name;

    /**
     * Model 中文
     *
     * @return string
     */
    abstract protected function name(): string;

    /**
     * 建構方法
     *
     * @return void
     */
    public function __construct()
    {
        $this->model_name = $this->name();
    }

    /**
     * 以 ID 取得指定資料
     *
     * @param int $id ID
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\EntityNotFoundException
     */
    public function find(int $id): Model
    {
        $record = $this->model->find($id);

        if (is_null($record)) {
            throw new EntityNotFoundException('找不到該 '.$this->model_name);
        }

        return $record;
    }

    /**
     * 建立單筆資料
     *
     * @param array<string,mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * 建立多筆資料
     *
     * @param array<array<string,mixed>> $data
     * @return bool
     */
    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * 更新指定資料
     *
     * @param int $id ID
     * @param array<string,mixed> $data 更新欄位與資料
     * @return bool
     */
    public function updateRecord(int $id, array $data): bool
    {
        return $this->model
            ->where('id', $id)
            ->update($data);
    }

    /**
     * 安全的更新指定資料
     *
     * @param int $id ID
     * @param array<string,mixed> $data 更新欄位與資料
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     */
    public function safeUpdateRecord(int $id, array $data): Model
    {
        DB::beginTransaction();

        try {
            $model = $this->model
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (is_null($model)) {
                throw new EntityNotFoundException('找不到該 '.$this->model_name);
            }

            foreach ($data as $key => $value) {
                $model->{$key} = $value;
            }

            $model->save();
        } catch (Exception $e) {
            report($e);

            DB::rollBack();

            throw $e;
        }

        DB::commit();

        return $model;
    }

    /**
     * 更新多筆資料
     *
     * @param int[] $ids ID
     * @param array<string,mixed> $data 更新欄位與資料
     * @return bool
     */
    public function updateRecords(array $ids, array $data): bool
    {
        return $this->model
            ->whereIn('id', $ids)
            ->update($data);
    }

    /**
     * 安全的更新多筆資料
     *
     * @param int[] $id ID
     * @param array<string,mixed> $data 更新欄位與資料
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     */
    public function safeUpdateRecords(array $ids, array $data): Model
    {
        DB::beginTransaction();

        try {
            $models = $this->model
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            foreach ($models as $model) {
                foreach ($data as $key => $value) {
                    $model->{$key} = $value;
                }

                $model->save();
            }
        } catch (Exception $e) {
            report($e);

            DB::rollBack();

            throw $e;
        }

        DB::commit();

        return $model;
    }

    /**
     * 刪除單筆資料
     *
     * @param int $id ID
     * @return bool
     */
    public function deleteRecord(int $id): bool
    {
        return $this->model
            ->where('id', $id)
            ->delete();
    }

    /**
     * 刪除多筆資料
     *
     * @param int[] $ids ID
     * @return bool
     */
    public function deleteRecords(array $ids): bool
    {
        return $this->model
            ->whereIn('id', $ids)
            ->delete();
    }
}
