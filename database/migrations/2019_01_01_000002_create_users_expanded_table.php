<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users_expanded')) {
            return;
        }

        Schema::create('users_expanded', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 128);
            $table->string('surname', 128);
            $table->unsignedInteger('profile_picture')->nullable();
            $table->json('wallpaper')->nullable();
            $table->longText('taskbar')->nullable();
            $table->tinyInteger('taskbar_size')->nullable();
            $table->string('type', 11)->default('student');
            $table->string('accent', 6)->nullable();
            $table->string('theme', 64)->nullable();
            $table->unsignedTinyInteger('plan')->default(1);
            $table->binary('twofa')->nullable();
            $table->string('deac_twofa', 128)->nullable();
            $table->tinyInteger('privacy_search_visible')->default(1);
            $table->tinyInteger('privacy_show_email')->default(0);
            $table->tinyInteger('privacy_show_username')->default(0);
            $table->unsignedTinyInteger('privacy_send_messages')->default(1);
            $table->unsignedTinyInteger('privacy_share_documents')->default(1);
            $table->unsignedTinyInteger('privacy_ms_office')->default(1);
            $table->timestamp('password_last_change')->nullable();
            $table->string('blocked', 1204)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_expanded');
    }
};
