<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('themes')->upsert(
            [
                ['id' => 1, 'author' => null, 'name' => 'Default', 'unique_name' => 'default', 'icon' => 'black'],
                ['id' => 2, 'author' => null, 'name' => 'Dark',    'unique_name' => 'dark',    'icon' => 'white'],
            ],
            ['id'],
            ['author', 'name', 'unique_name', 'icon'],
        );
    }
}
