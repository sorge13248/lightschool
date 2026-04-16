<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeFileUrls extends Command
{
    protected $signature = 'ls:normalize-file-urls
                            {--dry-run : Show affected rows without updating them}';

    protected $description = 'Replace backslashes with forward slashes in file.file_url (legacy Windows-style paths)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $affected = DB::table('file')
            ->whereNotNull('file_url')
            ->where('file_url', 'like', '%\\\\%')
            ->get(['id', 'file_url']);

        if ($affected->isEmpty()) {
            $this->info('No rows with backslashes found in file.file_url.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Found {$affected->count()} row(s) with backslashes in file_url.");

        if ($dryRun) {
            $this->table(['id', 'file_url (before)', 'file_url (after)'], $affected->map(fn($row) => [
                $row->id,
                $row->file_url,
                str_replace('\\', '/', $row->file_url),
            ]));
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($affected as $row) {
            DB::table('file')
                ->where('id', $row->id)
                ->update(['file_url' => str_replace('\\', '/', $row->file_url)]);
            $updated++;
        }

        $this->info("Updated {$updated} row(s).");
        return self::SUCCESS;
    }
}
