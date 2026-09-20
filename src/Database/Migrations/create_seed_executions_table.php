<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('superseeder.table'), function (Blueprint $table) {
            $table->id();
            $table->string('seeder')->index();
            $table->integer('batch')->index();
            $table->string('status')->default('ran');
            $table->unsignedBigInteger('execution_time_ms')->nullable();
            $table->json('tracked_records')->nullable();
            $table->json('tags')->nullable();
            $table->string('record_hash')->nullable();
            $table->boolean('record_hash_requires_unique_columns')->nullable();
            $table->string('seeder_hash')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('superseeder.table'));
    }
};
