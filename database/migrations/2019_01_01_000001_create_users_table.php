<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 249)->unique();
            $table->string('password', 255);
            $table->string('username', 100)->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('verified')->default(0);
            $table->unsignedTinyInteger('resettable')->default(1);
            $table->unsignedInteger('roles_mask')->default(0);
            $table->unsignedInteger('registered');
            $table->unsignedInteger('last_login')->nullable();
            $table->unsignedMediumInteger('force_logout')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
