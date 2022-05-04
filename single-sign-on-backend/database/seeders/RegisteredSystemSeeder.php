<?php

namespace Database\Seeders;

use App\Models\RegisteredSystem;
use Illuminate\Database\Seeder;

class RegisteredSystemSeeder extends Seeder
{
    /**
     * Model
     *
     * @var \Illuminate\Database\Eloquent\Model|\App\Models\RegisteredSystem
     */
    protected $model;

    /**
     * 建構方法
     *
     * @param \App\Models\RegisteredSystem $model
     * @return void
     */
    public function __construct(RegisteredSystem $model)
    {
        $this->model = $model;
    }

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $seeds = [
            [
                'name' => 'external_user_authorization',
                'is_valid' => 1,
            ],
            [
                'name' => 'single_sign_on',
                'is_valid' => 1,
            ],
        ];

        foreach ($seeds as $seed) {
            $this->model::create($seed);
        }
    }
}
