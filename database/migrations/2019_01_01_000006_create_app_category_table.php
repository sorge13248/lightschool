<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_category')) {
            return;
        }

        Schema::create('app_category', function (Blueprint $table) {
            $table->string('name', 32)->primary();
            $table->string('sub', 32)->nullable();
            $table->tinyInteger('visible')->default(1);
            $table->tinyInteger('icon')->default(1);
            $table->string('name_en', 64);
            $table->string('name_it', 64);
            $table->index('sub');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_category');
    }
};
