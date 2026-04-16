<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('themes')) {
            return;
        }

        Schema::create('themes', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('author')->nullable();
            $table->string('name', 64);
            $table->string('unique_name', 64);
            $table->string('icon', 5)->default('black')->comment('Allowed values: white/black');
            $table->index('author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
