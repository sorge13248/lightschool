<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        $updateCols = ['timestamp'];

        DB::table('apps')->upsert([
            ['unique_name' => 'contact',      'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'desktop',      'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'diary',        'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'file-manager', 'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'message',      'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'project',      'timestamp' => '2019-08-10 08:40:00'],
            ['unique_name' => 'reader',       'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'settings',     'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'share',        'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'timetable',    'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'trash',        'timestamp' => '2019-01-01 07:00:00'],
            ['unique_name' => 'writer',       'timestamp' => '2019-01-01 07:00:00'],
        ], ['unique_name'], $updateCols);
    }
}
