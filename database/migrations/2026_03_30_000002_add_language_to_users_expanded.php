<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_expanded', function (Blueprint $table) {
            $table->string('language', 10)->nullable()->after('theme')->default('it')->comment('User\'s preferred language (e.g. "en", "it"). Used for UI and email localization.')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users_expanded', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
