<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('file')) {
            return;
        }

        Schema::create('file', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->enum('type', ['folder', 'notebook', 'diary', 'file'])->comment('Cartella, quaderno, diario o file');
            $table->string('name', 128);
            $table->string('diary_type', 24)->nullable()->comment('Tipo diario');
            $table->date('diary_date')->nullable();
            $table->date('diary_reminder')->nullable();
            $table->tinyInteger('diary_priority')->default(0)->comment('Priorita diario');
            $table->string('diary_color', 6)->nullable();
            $table->tinyInteger('n_ver')->default(1);
            $table->longText('header')->nullable();
            $table->binary('cypher')->nullable();
            $table->binary('html')->nullable();
            $table->longText('footer')->nullable();
            $table->longText('file_url')->nullable()->comment('Indirizzo URL file');
            $table->string('file_type', 255)->nullable()->comment('Tipo file');
            $table->unsignedInteger('file_size')->nullable()->comment('Dimensione file');
            $table->tinyInteger('fav')->default(0);
            $table->string('icon', 24)->nullable()->comment('Icona');
            $table->timestamp('create_date')->useCurrent()->comment('Data di creazione');
            $table->timestamp('last_view')->nullable()->comment('Ultima vista');
            $table->timestamp('last_edit')->nullable()->comment('Ultima modifica');
            $table->unsignedInteger('folder')->nullable();
            $table->tinyInteger('trash')->default(0);
            $table->unsignedInteger('history')->nullable();
            $table->timestamp('bypass')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index('history');
            $table->index('deleted_at');
            $table->index('trash');
            $table->index('folder');
            $table->index('fav');
            $table->index('type');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file');
    }
};
