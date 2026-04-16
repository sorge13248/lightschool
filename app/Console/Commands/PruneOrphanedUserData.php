<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneOrphanedUserData extends Command
{
    protected $signature = 'ls:prune-orphaned-user-data
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete rows in user-related tables and filesystem data that reference non-existent users';

    /**
     * Tables and their user FK columns.
     * Each entry: [table, fk_column]
     */
    private array $tables = [
        ['users_expanded',         'id'],
        ['users_audit_log',        'user_id'],
        ['users_confirmations',    'user_id'],
        ['users_school',           'user'],
        ['access',                 'user'],
        ['contact',                'user_id'],
        ['file',                   'user_id'],
        ['message_actors',         'user_id'],
        ['message_chat',           'sender'],
        ['share',                  'sender'],
        ['share',                  'receiving'],
        ['timetable',              'user'],
        ['project_files',          'user'],
        ['crypto_migration_state', 'user_id'],
        ['sessions',               'user_id'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $userIds = DB::table('users')->pluck('id');

        if ($userIds->isEmpty()) {
            $this->warn('No users found — aborting to avoid wiping everything.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s orphaned data for %d existing users.',
            $dryRun ? 'Counting' : 'Pruning',
            $userIds->count()
        ));

        $totalDeleted = 0;

        // ── Database rows ─────────────────────────────────────────────────────

        foreach ($this->tables as [$table, $fk]) {
            if (!$this->tableExists($table)) {
                $this->line("  <comment>skip</comment>  {$table}.{$fk} (table not found)");
                continue;
            }

            $count = DB::table($table)
                ->whereNotNull($fk)
                ->whereNotIn($fk, $userIds)
                ->count();

            if ($count === 0) {
                continue;
            }

            if ($dryRun) {
                $this->line("  <info>would delete</info>  {$count} row(s) from <comment>{$table}</comment> ({$fk})");
            } else {
                DB::table($table)
                    ->whereNotNull($fk)
                    ->whereNotIn($fk, $userIds)
                    ->delete();

                $this->line("  <info>deleted</info>  {$count} row(s) from <comment>{$table}</comment> ({$fk})");
            }

            $totalDeleted += $count;
        }

        // ── Uploads (storage/app/uploads/{userId}/) ───────────────────────────

        $this->pruneFilesystemDirs(
            disk: 'uploads',
            label: 'uploads',
            userIds: $userIds,
            dryRun: $dryRun,
            totalDeleted: $totalDeleted,
        );

        // ── Keyring (secure_dir/keyring/{userId}/) ────────────────────────────

        $this->pruneKeyringDirs(
            userIds: $userIds,
            dryRun: $dryRun,
            totalDeleted: $totalDeleted,
        );

        $this->newLine();
        $this->info(sprintf(
            '%s %d orphaned item(s) in total.',
            $dryRun ? 'Would remove' : 'Removed',
            $totalDeleted
        ));

        return self::SUCCESS;
    }

    private function pruneFilesystemDirs(
        string $disk,
        string $label,
        \Illuminate\Support\Collection $userIds,
        bool $dryRun,
        int &$totalDeleted,
    ): void {
        $root = Storage::disk($disk)->path('');

        if (!is_dir($root)) {
            return;
        }

        foreach (scandir($root) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            // Only process numeric directories that look like user IDs
            if (!ctype_digit($entry)) {
                continue;
            }

            $userId = (int) $entry;

            if ($userIds->contains($userId)) {
                continue;
            }

            $path = $root . DIRECTORY_SEPARATOR . $entry;

            if ($dryRun) {
                $this->line("  <info>would delete</info>  {$label} dir: <comment>{$path}</comment>");
            } else {
                $this->deleteDirectory($path);
                $this->line("  <info>deleted</info>  {$label} dir: <comment>{$path}</comment>");
            }

            $totalDeleted++;
        }
    }

    private function pruneKeyringDirs(
        \Illuminate\Support\Collection $userIds,
        bool $dryRun,
        int &$totalDeleted,
    ): void {
        $secureDir = config('lightschool.secure_dir', storage_path('secure'));
        $keyringDir = $secureDir . DIRECTORY_SEPARATOR . 'keyring';

        if (!is_dir($keyringDir)) {
            return;
        }

        foreach (scandir($keyringDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (!ctype_digit($entry)) {
                continue;
            }

            $userId = (int) $entry;

            if ($userIds->contains($userId)) {
                continue;
            }

            $path = $keyringDir . DIRECTORY_SEPARATOR . $entry;

            if ($dryRun) {
                $this->line("  <info>would delete</info>  keyring dir: <comment>{$path}</comment>");
            } else {
                $this->deleteDirectory($path);
                $this->line("  <info>deleted</info>  keyring dir: <comment>{$path}</comment>");
            }

            $totalDeleted++;
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            @unlink($path);
            return;
        }

        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->deleteDirectory($path . DIRECTORY_SEPARATOR . $entry);
        }

        rmdir($path);
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
