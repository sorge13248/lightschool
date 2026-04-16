<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('access')) {
            return;
        }

        Schema::create('access', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->unsignedInteger('user');
            $table->string('date', 19);
            $table->string('ip', 32);
            $table->tinyInteger('allow')->default(1);
            $table->tinyInteger('logged_in')->default(1);
            $table->string('agent', 512)->nullable()->comment('User agent');
            $table->string('type', 24)->nullable();
            $table->index('user', 'user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access');
    }
};
