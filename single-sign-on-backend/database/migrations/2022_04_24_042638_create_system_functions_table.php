<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemFunctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_functions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('url_prefix', 50);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('update_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_functions');
    }
}
