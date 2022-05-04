<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uri', 500);
            $table->string('method', 10);
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('system_id')->unsigned()->nullable();
            $table->string('access_ip', 20)->nullable();
            $table->smallInteger('http_status')->unsigned();
            $table->json('request_payloads')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('system_id')->references('id')->on('registered_systems')->restrictOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('action_logs');
    }
}
