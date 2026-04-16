<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('message_list')) Schema::create('message_list', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('subject', 255)->nullable();
            $table->timestamp('timestamp')->useCurrent();
        });

        if (!Schema::hasTable('message_chat')) Schema::create('message_chat', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('message_list_id');
            $table->unsignedInteger('sender')->comment('Chi invia');
            $table->binary('cypher')->nullable();
            $table->binary('body')->nullable();
            $table->binary('attachment')->nullable();
            $table->timestamp('date')->nullable()->useCurrent()->comment('Data');
            $table->timestamp('read_at')->nullable()->comment('Letto o no');
            $table->index('sender');
        });

        if (!Schema::hasTable('message_actors')) Schema::create('message_actors', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('list_id');
            $table->unsignedInteger('user_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_actors');
        Schema::dropIfExists('message_chat');
        Schema::dropIfExists('message_list');
    }
};
