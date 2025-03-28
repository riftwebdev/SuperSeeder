<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('seeder_executions', function (Blueprint $table) {
            $table->id();
            $table->string('seeder')->index();
            $table->integer('batch')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('seeder_executions');
    }
};