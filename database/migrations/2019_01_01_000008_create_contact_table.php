<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact')) {
            return;
        }

        Schema::create('contact', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->comment('ID univoco');
            $table->unsignedInteger('user_id')->comment('ID utente di chi salva il contatto');
            $table->string('name', 128)->comment('Nome del contatto');
            $table->string('surname', 128)->nullable()->comment('Cognome del contatto');
            $table->unsignedInteger('contact_id')->nullable()->comment('ID del contatto');
            $table->tinyInteger('fav')->default(0);
            $table->tinyInteger('trash')->default(0);
            $table->tinyInteger('deleted')->default(0);
            $table->index('contact_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact');
    }
};
