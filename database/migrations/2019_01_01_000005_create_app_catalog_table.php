<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_catalog')) {
            return;
        }

        Schema::create('app_catalog', function (Blueprint $table) {
            $table->string('unique_name', 64)->primary();
            $table->float('version')->unsigned()->default(1);
            $table->string('category', 32);
            $table->unsignedInteger('author')->nullable();
            $table->tinyInteger('visible')->default(1);
            $table->unsignedTinyInteger('icon')->default(1);
            $table->unsignedTinyInteger('is_system')->default(0);
            $table->unsignedTinyInteger('settings')->default(0)->comment('App has settings page');
            $table->string('name_en', 32);
            $table->string('name_it', 32);
            $table->longText('detail_en')->nullable();
            $table->longText('detail_it')->nullable();
            $table->string('features', 512)->nullable();
            $table->unsignedTinyInteger('preview')->default(0);
            $table->timestamp('timestamp')->useCurrent();
            $table->string('t_icon', 5)->nullable();
            $table->index('category');
            $table->index('author');
            $table->index('visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_catalog');
    }
};
