<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('country')) {
            return;
        }

        Schema::create('country', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country');
    }
};
