<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_purchase')) {
            return;
        }

        Schema::create('app_purchase', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user');
            $table->string('app', 64);
            $table->timestamp('timestamp')->useCurrent();
            $table->unsignedTinyInteger('application_launcher')->default(1);
            $table->longText('data')->nullable();
            $table->unique(['user', 'app']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_purchase');
    }
};
