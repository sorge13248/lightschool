<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users_confirmations')) Schema::create('users_confirmations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('email', 249);
            $table->string('selector', 16)->unique();
            $table->string('token', 255);
            $table->unsignedInteger('expires');
        });

        if (!Schema::hasTable('users_resets')) Schema::create('users_resets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->unsignedInteger('user');
            $table->string('selector', 20)->unique();
            $table->string('token', 255);
            $table->unsignedInteger('expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_resets');
        Schema::dropIfExists('users_confirmations');
    }
};
