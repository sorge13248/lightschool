<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\MessageChat;
use App\Models\User;
use App\Models\UserExpanded;
use App\Services\KeyringService;
use App\Services\LegacyCryptoService;
use App\Services\SodiumCryptoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * One-time upgrade command for LightSchool 2020 production databases.
 *
 * Runs in three sequential phases:
 *
 *  Phase 1 — Schema upgrades
 *    1.  CSV → JSON  (taskbar, blocked)
 *    2.  String timestamps → proper TIMESTAMP columns  (access.date, file.last_view, file.last_edit)
 *    3.  file.deleted       → file.deleted_at
 *    4.  timetable.deleted  → timetable.deleted_at
 *    5.  app_catalog.isSystem → is_system
 *    6.  message_chat.is_read → read_at
 *    7.  Missing indexes
 *    8.  Recreate views
 *    9.  Crypto schema prep  (enc_version columns, widen BLOB columns, state table)
 *
 *  Phase 2 — Crypto re-encryption
 *    For each user: generate Ed25519 key pair, migrate files / messages / 2FA
 *    from Defuse+RSA-2048 to XChaCha20-Poly1305+X25519.
 *
 *  Phase 3 — Finalization
 *    Convert BLOB/LONGBLOB → TEXT/LONGTEXT, drop state table, drop enc_version columns.
 *    Aborted if any re-encryption failures occurred in Phase 2.
 */
class UpgradeLS2020Schema extends Command
{
    protected $signature   = 'ls:upgrade-2020-schema';
    protected $description = 'One-time schema upgrade + crypto re-encryption for LightSchool 2020';

    private const BATCH = 100;

    private int $countDone   = 0;
    private int $countFailed = 0;

    public function __construct(
        private readonly KeyringService      $keyring,
        private readonly LegacyCryptoService $legacy,
        private readonly SodiumCryptoService $sodium,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ══════════════════════════════════════════════════════════════════════
        // PHASE 1 — Schema upgrades
        // ══════════════════════════════════════════════════════════════════════

        // ── 1 & 2.  CSV → JSON ───────────────────────────────────────────────
        $this->info('Converting CSV columns to JSON...');
        $this->csvToJson('users_expanded', 'taskbar', 'int');
        $this->csvToJson('users_expanded', 'blocked', 'int');

        Schema::table('users_expanded', function (Blueprint $table) {
            $table->json('taskbar')->nullable()->change();
            $table->string('blocked', 1204)->nullable()->change();
        });

        DB::statement("ALTER TABLE `users_expanded` MODIFY COLUMN `blocked` JSON NULL");

        // ── 3 – 5.  String timestamps → proper TIMESTAMP columns ─────────────
        $this->info('Converting string timestamps to TIMESTAMP columns...');

        DB::statement("ALTER TABLE `access` ADD COLUMN `date_tmp` TIMESTAMP NULL AFTER `date`");
        DB::statement("UPDATE `access` SET `date_tmp` = CASE
            WHEN `date` REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`date`, '%Y-%m-%d %H:%i:%s')
            WHEN `date` REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`date`, '%d/%m/%Y %H:%i:%s')
            ELSE NULL
        END WHERE `date` IS NOT NULL");
        DB::statement("ALTER TABLE `access` DROP COLUMN `date`");
        DB::statement("ALTER TABLE `access` CHANGE COLUMN `date_tmp` `date` TIMESTAMP NULL");

        DB::statement("ALTER TABLE `file` ADD COLUMN `last_view_tmp` TIMESTAMP NULL AFTER `last_view`");
        DB::statement("UPDATE `file` SET `last_view_tmp` = CASE
            WHEN `last_view` REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`last_view`, '%Y-%m-%d %H:%i:%s')
            WHEN `last_view` REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`last_view`, '%d/%m/%Y %H:%i:%s')
            ELSE NULL
        END WHERE `last_view` IS NOT NULL");
        DB::statement("ALTER TABLE `file` DROP COLUMN `last_view`");
        DB::statement("ALTER TABLE `file` CHANGE COLUMN `last_view_tmp` `last_view` TIMESTAMP NULL");

        DB::statement("ALTER TABLE `file` ADD COLUMN `last_edit_tmp` TIMESTAMP NULL AFTER `last_edit`");
        DB::statement("UPDATE `file` SET `last_edit_tmp` = CASE
            WHEN `last_edit` REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`last_edit`, '%Y-%m-%d %H:%i:%s')
            WHEN `last_edit` REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(`last_edit`, '%d/%m/%Y %H:%i:%s')
            ELSE NULL
        END WHERE `last_edit` IS NOT NULL");
        DB::statement("ALTER TABLE `file` DROP COLUMN `last_edit`");
        DB::statement("ALTER TABLE `file` CHANGE COLUMN `last_edit_tmp` `last_edit` TIMESTAMP NULL");

        // ── 6.  file.deleted → file.deleted_at ──────────────────────────────
        $this->info('Renaming columns...');
        Schema::table('file', function (Blueprint $table) {
            $table->renameColumn('deleted', 'deleted_at');
        });

        // ── 7.  timetable.deleted → timetable.deleted_at ────────────────────
        Schema::table('timetable', function (Blueprint $table) {
            $table->renameColumn('deleted', 'deleted_at');
        });

        // ── 8.  app_catalog.isSystem → is_system ────────────────────────────
        Schema::table('app_catalog', function (Blueprint $table) {
            $table->renameColumn('isSystem', 'is_system');
        });

        // ── 9.  message_chat.is_read → read_at ──────────────────────────────
        Schema::table('message_chat', function (Blueprint $table) {
            $table->renameColumn('is_read', 'read_at');
        });

        // ── 10. Missing indexes ───────────────────────────────────────────────
        $this->info('Adding indexes...');
        Schema::table('file', function (Blueprint $table) {
            $table->index('diary_date', 'file_diary_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('username', 'users_username');
        });

        $userIdIndex = DB::selectOne("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'message_actors'
                AND COLUMN_NAME  = 'user_id'
                AND SEQ_IN_INDEX = 1
                AND INDEX_NAME  != 'PRIMARY'
            LIMIT 1
        ");
        if ($userIdIndex) {
            DB::statement("ALTER TABLE `message_actors` DROP INDEX `{$userIdIndex->INDEX_NAME}`");
        }

        Schema::table('message_actors', function (Blueprint $table) {
            $table->index(['list_id', 'user_id'], 'message_actors_list_user');
        });

        // ── 11. Recreate views ────────────────────────────────────────────────
        $this->info('Recreating views...');
        $this->recreateViews();

        // ── 12. Crypto schema prep ────────────────────────────────────────────
        $this->info('Preparing crypto schema...');
        $this->prepareCryptoSchema();

        // ══════════════════════════════════════════════════════════════════════
        // PHASE 2 — Crypto re-encryption
        // ══════════════════════════════════════════════════════════════════════

        $total = User::count();
        $this->info("Re-encrypting data for {$total} user(s)...");

        User::orderBy('id')->each(function (User $user) {
            $this->migrateUser($user);
        });

        $this->newLine();
        $this->info("Re-encryption done. Migrated: {$this->countDone} | Failed: {$this->countFailed}");

        if ($this->countFailed > 0) {
            $this->error("{$this->countFailed} entities failed — skipping finalization.");
            $this->line("Check <comment>crypto_migration_state</comment> WHERE status = 'failed' for details.");
            $this->line('Fix the issues and re-run, or run <comment>php artisan crypto:finalize</comment> manually after resolving failures.');
            return self::FAILURE;
        }

        // ══════════════════════════════════════════════════════════════════════
        // PHASE 3 — Finalization
        // ══════════════════════════════════════════════════════════════════════

        $this->finalizeCrypto();

        $this->info('Upgrade complete.');
        $this->line('');
        $this->line('Remaining manual step:');
        $this->line('  Delete archived RSA keys: <comment>rm -rf {SECURE_DIR}/keyring_archive/</comment>');

        return self::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Phase 1 helpers
    // ══════════════════════════════════════════════════════════════════════════

    private function prepareCryptoSchema(): void
    {
        // enc_version tracking columns (idempotent)
        Schema::table('file', function (Blueprint $table) {
            if (!Schema::hasColumn('file', 'enc_version')) {
                $table->tinyInteger('enc_version')->default(1)->after('deleted_at');
            }
        });

        Schema::table('message_chat', function (Blueprint $table) {
            if (!Schema::hasColumn('message_chat', 'enc_version')) {
                $table->tinyInteger('enc_version')->default(1)->after('read_at');
            }
        });

        Schema::table('users_expanded', function (Blueprint $table) {
            if (!Schema::hasColumn('users_expanded', 'enc_version')) {
                $table->tinyInteger('enc_version')->default(1)->after('blocked');
            }
        });

        // Sanitize zero-dates before any MODIFY COLUMN rebuilds the table
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''), ',,', ',')");
        DB::statement("UPDATE `file` SET `create_date` = '2000-01-01 00:00:00' WHERE `create_date` = '0000-00-00 00:00:00'");
        DB::statement("SET SESSION sql_mode = @@GLOBAL.sql_mode");

        // Widen binary columns to BLOB/LONGBLOB so legacy raw-binary Defuse
        // ciphertext and new base64 sodium ciphertext can coexist
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `html` LONGBLOB NULL');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `cypher` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `body` LONGBLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `cypher` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `attachment` LONGBLOB NULL');
        DB::statement('ALTER TABLE `users_expanded` MODIFY COLUMN `twofa` BLOB NULL');

        // Temporary migration state table (idempotent)
        if (!Schema::hasTable('crypto_migration_state')) {
            Schema::create('crypto_migration_state', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->enum('entity_type', ['file', 'message', 'twofa']);
                $table->unsignedBigInteger('entity_id');
                $table->unsignedInteger('user_id');
                $table->enum('status', ['pending', 'done', 'failed', 'skipped'])->default('pending');
                $table->text('error')->nullable();
                $table->timestamp('migrated_at')->nullable();
                $table->unique(['entity_type', 'entity_id'], 'uq_crypto_entity');
                $table->index('user_id');
                $table->index('status');
            });
        }
    }

    private function csvToJson(string $table, string $column, string $cast = 'string'): void
    {
        DB::table($table)->whereNotNull($column)->orderBy('id')->each(function ($row) use ($table, $column, $cast) {
            $raw     = $row->$column;
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return;
            }

            $items = array_values(array_filter(
                array_map('trim', explode(',', $raw)),
                fn($v) => $v !== ''
            ));

            if ($cast === 'int') {
                $items = array_map('intval', $items);
            }

            DB::table($table)->where('id', $row->id)->update([$column => json_encode($items)]);
        });
    }

    private function recreateViews(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW `all_users` AS
            SELECT
                `users`.`id`,
                `users`.`email`,
                `users`.`password`,
                `users`.`username`,
                `users`.`status`,
                `users`.`verified`,
                `users`.`resettable`,
                `users`.`roles_mask`,
                `users`.`registered`,
                `users`.`last_login`,
                `users`.`force_logout`,
                `users_expanded`.`name`,
                `users_expanded`.`surname`,
                `users_expanded`.`profile_picture`,
                `users_expanded`.`wallpaper`,
                `users_expanded`.`taskbar`,
                `users_expanded`.`type`,
                `users_expanded`.`accent`,
                `users_expanded`.`theme`,
                `users_expanded`.`plan`,
                `users_expanded`.`twofa`,
                `users_expanded`.`privacy_search_visible`,
                `users_expanded`.`privacy_show_email`,
                `users_expanded`.`privacy_show_username`,
                `users_expanded`.`privacy_send_messages`,
                `users_expanded`.`privacy_ms_office`,
                `users_expanded`.`privacy_share_documents`,
                `users_expanded`.`password_last_change`,
                `users_expanded`.`blocked`,
                `users_expanded`.`taskbar_size`
            FROM `users`
            JOIN `users_expanded` ON `users`.`id` = `users_expanded`.`id`
        ");

        DB::statement("
            CREATE OR REPLACE VIEW `desktop` AS
            SELECT
                `file`.`user_id`        AS `user_id`,
                `file`.`id`             AS `id`,
                `file`.`name`           AS `name`,
                `file`.`type`           AS `type`,
                NULL                    AS `surname`,
                `file`.`diary_type`     AS `diary_type`,
                `file`.`diary_priority` AS `diary_priority`,
                `file`.`diary_date`     AS `diary_date`,
                `file`.`diary_color`    AS `diary_color`,
                `file`.`icon`           AS `icon`,
                NULL                    AS `username`,
                `file`.`file_url`       AS `file_url`,
                `file`.`file_type`      AS `file_type`,
                `file`.`deleted_at`     AS `deleted_at`
            FROM `file`
            WHERE `file`.`fav` = 1
              AND `file`.`history` IS NULL
              AND `file`.`trash` = 0
            UNION
            SELECT
                `contact`.`user_id`                AS `user_id`,
                `contact`.`id`                     AS `id`,
                `contact`.`name`                   AS `name`,
                'contact'                          AS `type`,
                `contact`.`surname`                AS `surname`,
                NULL                               AS `diary_type`,
                NULL                               AS `diary_priority`,
                NULL                               AS `diary_date`,
                NULL                               AS `diary_color`,
                `users_expanded`.`profile_picture` AS `icon`,
                `users`.`username`                 AS `username`,
                NULL                               AS `file_url`,
                NULL                               AS `file_type`,
                `contact`.`deleted`                AS `deleted_at`
            FROM `contact`
            JOIN `users_expanded` ON `contact`.`contact_id` = `users_expanded`.`id`
            JOIN `users` ON `users_expanded`.`id` = `users`.`id`
            WHERE `contact`.`fav` = 1
        ");
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Phase 2 — Per-user crypto migration
    // ══════════════════════════════════════════════════════════════════════════

    private function migrateUser(User $user): void
    {
        $userId = $user->id;
        $this->line("  User #{$userId} ({$user->username})");

        $this->keyring->generateEd25519KeyPair($userId);
        $this->populateStateTable($userId);
        $this->migrateFiles($userId);
        $this->migrateMessages($userId);
        $this->migrateTwofa($userId);

        if ($this->hasNoFailures($userId)) {
            $this->archiveLegacyKeys($userId);
        }
    }

    private function populateStateTable(int $userId): void
    {
        File::where('user_id', $userId)
            ->where('enc_version', 1)
            ->whereNotNull('html')
            ->select('id')
            ->each(function (File $file) use ($userId) {
                DB::table('crypto_migration_state')->insertOrIgnore([
                    'entity_type' => 'file',
                    'entity_id'   => $file->id,
                    'user_id'     => $userId,
                    'status'      => 'pending',
                ]);
            });

        MessageChat::where('sender', $userId)
            ->where('enc_version', 1)
            ->select('id')
            ->each(function (MessageChat $msg) use ($userId) {
                DB::table('crypto_migration_state')->insertOrIgnore([
                    'entity_type' => 'message',
                    'entity_id'   => $msg->id,
                    'user_id'     => $userId,
                    'status'      => 'pending',
                ]);
            });

        $expanded = UserExpanded::find($userId);
        if ($expanded && $expanded->twofa !== null && $expanded->enc_version === 1) {
            DB::table('crypto_migration_state')->insertOrIgnore([
                'entity_type' => 'twofa',
                'entity_id'   => $userId,
                'user_id'     => $userId,
                'status'      => 'pending',
            ]);
        }
    }

    private function migrateFiles(int $userId): void
    {
        File::where('user_id', $userId)
            ->where('enc_version', 1)
            ->whereNotNull('html')
            ->orderBy('id')
            ->chunk(self::BATCH, function ($files) use ($userId) {
                foreach ($files as $file) {
                    $this->migrateFile($file, $userId);
                }
            });
    }

    private function migrateFile(File $file, int $userId): void
    {
        try {
            $plaintext = $file->cypher !== null
                ? $this->legacy->decrypt($file->html, $file->cypher, $userId)
                : $file->html; // legacy unencrypted notebook

            $encrypted = $this->sodium->encrypt($plaintext, $userId);

            File::where('id', $file->id)->update([
                'html'        => $encrypted['data'],
                'cypher'      => $encrypted['key'],
                'enc_version' => 2,
            ]);

            $this->markDone('file', $file->id);
            $this->countDone++;
        } catch (\Throwable $e) {
            $this->markFailed('file', $file->id, $e->getMessage());
            $this->countFailed++;
            $this->warn("    [FAIL] file#{$file->id}: {$e->getMessage()}");
        }
    }

    private function migrateMessages(int $userId): void
    {
        MessageChat::where('sender', $userId)
            ->where('enc_version', 1)
            ->orderBy('id')
            ->chunk(self::BATCH, function ($messages) use ($userId) {
                foreach ($messages as $msg) {
                    $this->migrateMessage($msg, $userId);
                }
            });
    }

    private function migrateMessage(MessageChat $msg, int $userId): void
    {
        try {
            $body       = $this->legacy->decrypt($msg->body, $msg->cypher, $userId);
            $attachment = null;
            $attachNote = null;

            if ($msg->attachment !== null) {
                try {
                    $attachment = $this->legacy->decrypt($msg->attachment, $msg->cypher, $userId);
                } catch (\Throwable $e) {
                    $attachNote = 'attachment nulled (legacy key mismatch): ' . $e->getMessage();
                }
            }

            $encrypted = $this->sodium->encryptMessage($body, $attachment, $userId);

            MessageChat::where('id', $msg->id)->update([
                'body'        => $encrypted['body'],
                'attachment'  => $encrypted['attachment'],
                'cypher'      => $encrypted['key'],
                'enc_version' => 2,
            ]);

            $this->markDone('message', $msg->id, $attachNote);
            $this->countDone++;

            if ($attachNote) {
                $this->warn("    [WARN] message#{$msg->id}: {$attachNote}");
            }
        } catch (\Throwable $e) {
            $this->markFailed('message', $msg->id, $e->getMessage());
            $this->countFailed++;
            $this->warn("    [FAIL] message#{$msg->id}: {$e->getMessage()}");
        }
    }

    private function migrateTwofa(int $userId): void
    {
        $expanded = UserExpanded::find($userId);

        if (!$expanded || $expanded->twofa === null || $expanded->enc_version !== 1) {
            return;
        }

        try {
            $totpSecret = $this->legacy->decryptTwofa($expanded->twofa, $userId);
            $encrypted  = $this->sodium->encryptTwofa($totpSecret, $userId);

            UserExpanded::where('id', $userId)->update([
                'twofa'       => $encrypted,
                'enc_version' => 2,
            ]);

            $this->markDone('twofa', $userId);
            $this->countDone++;
        } catch (\Throwable $e) {
            $this->markFailed('twofa', $userId, $e->getMessage());
            $this->countFailed++;
            $this->warn("    [FAIL] twofa for user#{$userId}: {$e->getMessage()}");
        }
    }

    private function archiveLegacyKeys(int $userId): void
    {
        if (!$this->keyring->hasKeyPair($userId)) {
            return;
        }
        try {
            $this->keyring->archiveLegacyKeyPair($userId);
        } catch (\Throwable $e) {
            $this->warn("    [WARN] Could not archive legacy keys for user#{$userId}: {$e->getMessage()}");
        }
    }

    private function markDone(string $type, int $entityId, ?string $note = null): void
    {
        DB::table('crypto_migration_state')
            ->where('entity_type', $type)
            ->where('entity_id', $entityId)
            ->update(['status' => 'done', 'error' => $note, 'migrated_at' => now()]);
    }

    private function markFailed(string $type, int $entityId, string $error): void
    {
        DB::table('crypto_migration_state')
            ->updateOrInsert(
                ['entity_type' => $type, 'entity_id' => $entityId],
                ['status' => 'failed', 'error' => $error, 'migrated_at' => now()]
            );
    }

    private function hasNoFailures(int $userId): bool
    {
        return DB::table('crypto_migration_state')
            ->where('user_id', $userId)
            ->where('status', 'failed')
            ->doesntExist();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Phase 3 — Finalization
    // ══════════════════════════════════════════════════════════════════════════

    private function finalizeCrypto(): void
    {
        // Sanitize any remaining zero-dates before the table rebuild
        $this->info('Sanitizing legacy zero-dates...');
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''), ',,', ',')");
        DB::statement("UPDATE `file` SET `create_date`    = '2000-01-01 00:00:00' WHERE `create_date`    = '0000-00-00 00:00:00'");
        DB::statement("UPDATE `file` SET `diary_date`     = NULL                  WHERE `diary_date`     = '0000-00-00'");
        DB::statement("UPDATE `file` SET `diary_reminder` = NULL                  WHERE `diary_reminder` = '0000-00-00'");
        DB::statement("SET SESSION sql_mode = @@GLOBAL.sql_mode");

        // Convert BLOB/LONGBLOB → TEXT/LONGTEXT now that all values are base64 ASCII
        $this->info('Converting columns from BLOB to TEXT...');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `html` LONGTEXT NULL');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `cypher` TEXT NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `body` LONGTEXT NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `cypher` TEXT NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `attachment` LONGTEXT NULL');
        DB::statement('ALTER TABLE `users_expanded` MODIFY COLUMN `twofa` TEXT NULL');

        // Drop temporary state table
        $this->info('Dropping crypto_migration_state...');
        Schema::dropIfExists('crypto_migration_state');

        // Drop enc_version columns
        $this->info('Dropping enc_version columns...');
        Schema::table('file',           fn($t) => $t->dropColumn('enc_version'));
        Schema::table('message_chat',   fn($t) => $t->dropColumn('enc_version'));
        Schema::table('users_expanded', fn($t) => $t->dropColumn('enc_version'));
    }
}
