<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('plan')->insertOrIgnore([
            ['id' => 1, 'name' => 'basic', 'disk_space' => 10],
            ['id' => 2, 'name' => 'admin', 'disk_space' => 100],
        ]);
    }
}
