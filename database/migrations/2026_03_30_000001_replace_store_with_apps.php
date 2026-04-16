<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create new apps table
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('unique_name', 64)->unique();
            $table->timestamp('timestamp')->useCurrent();
        });

        // 2. Copy apps from app_catalog (exclude store and themes)
        if (Schema::hasTable('app_catalog')) {
            DB::statement("
                INSERT INTO apps (unique_name, timestamp)
                SELECT unique_name, timestamp
                FROM app_catalog
                WHERE unique_name NOT IN ('store', 't-dark', 't-default', 'quiz', 'register', 'reader')
            ");

            // 3. Migrate users_expanded.taskbar: app_purchase.id → apps.id
            if (Schema::hasTable('app_purchase')) {
                DB::table('users_expanded')
                    ->whereNotNull('taskbar')
                    ->get(['id', 'taskbar'])
                    ->each(function ($user) {
                        $purchaseIds = json_decode($user->taskbar, true);
                        if (!is_array($purchaseIds) || empty($purchaseIds)) {
                            return;
                        }

                        $newTaskbar = [];
                        foreach ($purchaseIds as $purchaseId) {
                            $purchase = DB::table('app_purchase')
                                ->where('id', (int) $purchaseId)
                                ->first(['app']);

                            if (!$purchase) {
                                continue;
                            }

                            // Skip removed apps
                            if (in_array($purchase->app, ['store', 't-dark', 't-default'])) {
                                continue;
                            }

                            $app = DB::table('apps')
                                ->where('unique_name', $purchase->app)
                                ->first(['id']);

                            if ($app) {
                                $newTaskbar[] = $app->id;
                            }
                        }

                        DB::table('users_expanded')
                            ->where('id', $user->id)
                            ->update(['taskbar' => json_encode(array_values($newTaskbar))]);
                    });
            }

            // 4. Drop old tables (disable FK checks to avoid constraint errors)
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Schema::dropIfExists('app_purchase');
            Schema::dropIfExists('app_catalog');
            Schema::dropIfExists('app_category');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
