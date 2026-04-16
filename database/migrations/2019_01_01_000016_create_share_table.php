<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('share')) {
            return;
        }

        Schema::create('share', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->comment('ID univoco');
            $table->unsignedInteger('sender')->comment('Chi condivide');
            $table->unsignedInteger('receiving')->comment('Chi riceve');
            $table->unsignedInteger('file')->comment('ID del file condiviso');
            $table->string('comment', 128)->nullable()->comment('Commento di chi condivide');
            $table->timestamp('timestamp')->nullable()->useCurrent()->comment('Data di condivisione');
            $table->tinyInteger('edit')->default(0);
            $table->tinyInteger('deleted')->default(0);
            $table->index('receiving');
            $table->index('sender');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share');
    }
};
