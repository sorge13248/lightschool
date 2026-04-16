<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project')) Schema::create('project', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('code', 6)->unique('gen_id');
            $table->timestamp('timestamp')->useCurrent();
        });

        if (!Schema::hasTable('project_files')) Schema::create('project_files', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('project', 6);
            $table->unsignedInteger('file');
            $table->unsignedInteger('user');
            $table->unsignedTinyInteger('editable')->default(0);
            $table->unique(['project', 'file'], 'whiteboard_2');
            $table->index('project', 'whiteboard');
            $table->index('file');
            $table->index('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project');
    }
};
