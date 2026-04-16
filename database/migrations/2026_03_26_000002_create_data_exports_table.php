<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'processing', 'ready', 'downloaded', 'failed'])->default('pending');
            $table->string('zip_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_exports');
    }
};
