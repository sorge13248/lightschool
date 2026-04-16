<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan')) {
            return;
        }

        Schema::create('plan', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->autoIncrement();
            $table->string('name', 32);
            $table->unsignedInteger('disk_space')->comment('in MB');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan');
    }
};
