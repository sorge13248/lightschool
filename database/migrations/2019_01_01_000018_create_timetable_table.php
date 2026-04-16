<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timetable')) {
            return;
        }

        Schema::create('timetable', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->unsignedInteger('user');
            $table->string('year', 32)->nullable();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('slot');
            $table->string('subject', 64);
            $table->string('book', 64)->nullable();
            $table->string('fore', 6)->default('black');
            $table->timestamp('deleted_at')->nullable();
            $table->unique(['user', 'year', 'day', 'slot', 'deleted_at'], 'user');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable');
    }
};
