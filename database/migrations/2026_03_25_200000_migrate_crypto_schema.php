<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepares the schema for the crypto re-encryption migration.
 *
 * Changes:
 *  - Adds enc_version (TINYINT, default 1) to file, message_chat, users_expanded
 *  - Widens binary/blob cypher+html columns to TEXT/LONGTEXT to hold base64 ciphertext
 *  - Creates the temporary crypto_migration_state tracking table
 *
 * After running `php artisan crypto:migrate` and validating all rows are at enc_version=2:
 *  - Drop crypto_migration_state manually (or via a follow-up migration)
 *  - Remove enc_version columns (optional)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. enc_version columns ────────────────────────────────────────────
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

        // ── 2. Widen binary/blob columns to TEXT/LONGTEXT ─────────────────────
        // Fix legacy zero-dates in file.create_date before altering the table.
        // MariaDB strict mode rejects '0000-00-00 00:00:00' even in a WHERE clause,
        // so we must relax NO_ZERO_DATE / NO_ZERO_IN_DATE for this session first.
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''), ',,', ',')");
        DB::statement("UPDATE `file` SET `create_date` = '2000-01-01 00:00:00' WHERE `create_date` = '0000-00-00 00:00:00'");
        DB::statement("SET SESSION sql_mode = @@GLOBAL.sql_mode");

        // Widen to LONGBLOB/BLOB so both the legacy raw-binary Defuse ciphertext
        // and the new base64-encoded sodium ciphertexts can coexist in the same column.
        // TEXT/LONGTEXT would reject the binary content; BLOB is encoding-agnostic.
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `html` LONGBLOB NULL');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `cypher` BLOB NULL');

        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `body` LONGBLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `cypher` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `attachment` LONGBLOB NULL');

        DB::statement('ALTER TABLE `users_expanded` MODIFY COLUMN `twofa` BLOB NULL');

        // ── 3. Temporary migration state table ───────────────────────────────
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

    public function down(): void
    {
        Schema::dropIfExists('crypto_migration_state');

        Schema::table('users_expanded', function (Blueprint $table) {
            if (Schema::hasColumn('users_expanded', 'enc_version')) {
                $table->dropColumn('enc_version');
            }
        });

        Schema::table('message_chat', function (Blueprint $table) {
            if (Schema::hasColumn('message_chat', 'enc_version')) {
                $table->dropColumn('enc_version');
            }
        });

        Schema::table('file', function (Blueprint $table) {
            if (Schema::hasColumn('file', 'enc_version')) {
                $table->dropColumn('enc_version');
            }
        });

        // Revert column types back to BINARY/BLOB
        DB::statement('ALTER TABLE `users_expanded` MODIFY COLUMN `twofa` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `attachment` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `cypher` BLOB NULL');
        DB::statement('ALTER TABLE `message_chat` MODIFY COLUMN `body` BLOB NULL');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `cypher` BLOB NULL');
        DB::statement('ALTER TABLE `file` MODIFY COLUMN `html` LONGBLOB NULL');
    }
};
