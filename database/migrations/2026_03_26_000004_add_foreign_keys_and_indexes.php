<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds foreign key constraints and composite indexes across all active tables.
 *
 * Run `php artisan ls:prune-orphaned-user-data` before this migration to ensure
 * no orphaned rows exist that would cause FK constraint violations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addForeignKeys();
        $this->addIndexes();
    }

    public function down(): void
    {
        $this->dropIndexes();
        $this->dropForeignKeys();
    }

    // ── Foreign Keys ──────────────────────────────────────────────────────────

    private function addForeignKeys(): void
    {
        // users_expanded.id is the same as users.id (1-to-1 via shared PK)
        Schema::table('users_expanded', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('profile_picture')->references('id')->on('file')->onDelete('set null');
            $table->foreign('plan')->references('id')->on('plan')->onDelete('restrict');
        });

        Schema::table('users_confirmations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('users_resets', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('access', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('app_catalog', function (Blueprint $table) {
            $table->foreign('author')->references('id')->on('users')->onDelete('set null');
            $table->foreign('category')->references('name')->on('app_category')->onDelete('restrict');
        });

        Schema::table('app_purchase', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('app')->references('unique_name')->on('app_catalog')->onDelete('cascade');
        });

        Schema::table('contact', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // nullable — contact record can survive if the linked account is deleted
            $table->foreign('contact_id')->references('id')->on('users')->onDelete('set null');
        });

        // Null out folder references that point to non-existent files (orphaned data).
        // The subquery wrapper is required because MariaDB disallows referencing the
        // same table directly in an UPDATE … WHERE … IN (SELECT FROM same_table).
        DB::statement('
            UPDATE file SET folder = NULL
            WHERE folder IS NOT NULL
              AND folder NOT IN (SELECT id FROM (SELECT id FROM file) AS f)
        ');

        Schema::table('file', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // self-referential: clear parent folder pointer when the folder itself is deleted
            $table->foreign('folder')->references('id')->on('file')->onDelete('set null');
        });

        Schema::table('message_chat', function (Blueprint $table) {
            $table->foreign('message_list_id')->references('id')->on('message_list')->onDelete('cascade');
            $table->foreign('sender')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('message_actors', function (Blueprint $table) {
            $table->foreign('list_id')->references('id')->on('message_list')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('project_files', function (Blueprint $table) {
            $table->foreign('project')->references('code')->on('project')->onDelete('cascade');
            $table->foreign('file')->references('id')->on('file')->onDelete('cascade');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });

        // Delete share rows whose file, sender, or receiver no longer exist.
        DB::statement('DELETE FROM share WHERE file NOT IN (SELECT id FROM file)');
        DB::statement('DELETE FROM share WHERE sender NOT IN (SELECT id FROM users)');
        DB::statement('DELETE FROM share WHERE receiving NOT IN (SELECT id FROM users)');

        Schema::table('share', function (Blueprint $table) {
            $table->foreign('sender')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiving')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('file')->references('id')->on('file')->onDelete('cascade');
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->foreign('author')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('timetable', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    private function dropForeignKeys(): void
    {
        Schema::table('sessions',               fn (Blueprint $t) => $t->dropForeign(['user_id']));
        Schema::table('timetable',              fn (Blueprint $t) => $t->dropForeign(['user']));
        Schema::table('themes',                 fn (Blueprint $t) => $t->dropForeign(['author']));

        Schema::table('share', function (Blueprint $table) {
            $table->dropForeign(['sender']);
            $table->dropForeign(['receiving']);
            $table->dropForeign(['file']);
        });

        Schema::table('project_files', function (Blueprint $table) {
            $table->dropForeign(['project']);
            $table->dropForeign(['file']);
            $table->dropForeign(['user']);
        });

        Schema::table('message_actors', function (Blueprint $table) {
            $table->dropForeign(['list_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('message_chat', function (Blueprint $table) {
            $table->dropForeign(['message_list_id']);
            $table->dropForeign(['sender']);
        });

        Schema::table('file', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['folder']);
        });

        Schema::table('contact', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['contact_id']);
        });

        Schema::table('app_purchase', function (Blueprint $table) {
            $table->dropForeign(['user']);
            $table->dropForeign(['app']);
        });

        Schema::table('app_catalog', function (Blueprint $table) {
            $table->dropForeign(['author']);
            $table->dropForeign(['category']);
        });

        Schema::table('access',             fn (Blueprint $t) => $t->dropForeign(['user']));
        Schema::table('users_resets',       fn (Blueprint $t) => $t->dropForeign(['user']));
        Schema::table('users_confirmations',fn (Blueprint $t) => $t->dropForeign(['user_id']));

        Schema::table('users_expanded', function (Blueprint $table) {
            $table->dropForeign(['id']);
            $table->dropForeign(['profile_picture']);
            $table->dropForeign(['plan']);
        });
    }

    // ── Composite Indexes ─────────────────────────────────────────────────────

    private function addIndexes(): void
    {
        // file ----------------------------------------------------------------
        // Covers: browse active files in a folder (most common file query)
        Schema::table('file', function (Blueprint $table) {
            $table->index(['user_id', 'folder', 'trash', 'deleted_at'], 'idx_file_user_folder_state');
            // Covers: type-scoped queries (diary list, notebook list, etc.)
            $table->index(['user_id', 'type', 'trash', 'deleted_at'],   'idx_file_user_type_state');
            // Covers: bypass-token public access lookup
            $table->index(['bypass'],                                    'idx_file_bypass');
        });

        // share ---------------------------------------------------------------
        Schema::table('share', function (Blueprint $table) {
            // Covers: list received shares for a user, filtered by active
            $table->index(['receiving', 'deleted'], 'idx_share_receiving_deleted');
            // Covers: list sent shares for a user, filtered by active
            $table->index(['sender', 'deleted'],    'idx_share_sender_deleted');
            // Covers: check if a specific file is shared with a specific user
            $table->index(['file', 'receiving', 'deleted'], 'idx_share_file_receiving');
        });

        // contact -------------------------------------------------------------
        Schema::table('contact', function (Blueprint $table) {
            // Covers: list active/trashed contacts for a user
            $table->index(['user_id', 'deleted', 'trash'],       'idx_contact_user_state');
            // Covers: check whether a contact relationship already exists
            $table->index(['user_id', 'contact_id', 'deleted'], 'idx_contact_user_contact');
        });

        // message_actors & message_chat ---------------------------------------
        Schema::table('message_actors', function (Blueprint $table) {
            // Covers: find all threads a user participates in
            $table->index(['user_id', 'list_id'], 'idx_message_actors_user_list');
            // Covers: find all participants of a thread (JOIN + actor lookup)
            $table->index(['list_id'],            'idx_message_actors_list');
        });

        Schema::table('message_chat', function (Blueprint $table) {
            // Covers: paginated message history in a thread ordered by date
            $table->index(['message_list_id', 'date'],    'idx_message_chat_list_date');
            // Covers: check for unread messages in a thread
            $table->index(['message_list_id', 'read_at'], 'idx_message_chat_list_read');
        });

        // timetable -----------------------------------------------------------
        Schema::table('timetable', function (Blueprint $table) {
            // Covers: fetch all active entries for a user
            $table->index(['user', 'deleted_at'],       'idx_timetable_user_active');
            // Covers: day-specific schedule lookup
            $table->index(['user', 'day', 'deleted_at'], 'idx_timetable_user_day');
        });

        // data_exports --------------------------------------------------------
        Schema::table('data_exports', function (Blueprint $table) {
            // Covers: check for a pending/in-progress export before creating a new one
            $table->index(['user_id', 'status', 'expires_at'], 'idx_data_exports_user_status');
            // Covers: CleanExpiredDataExports command
            $table->index(['expires_at'],                      'idx_data_exports_expires');
        });

        // user_deletion_requests ----------------------------------------------
        Schema::table('user_deletion_requests', function (Blueprint $table) {
            // Covers: ProcessUserDeletions command fetching due requests
            $table->index(['deletion_timestamp'], 'idx_user_deletion_timestamp');
        });

        // project -------------------------------------------------------------
        Schema::table('project', function (Blueprint $table) {
            // Covers: project expiry window check (whereBetween timestamp)
            $table->index(['timestamp'], 'idx_project_timestamp');
        });
    }

    private function dropIndexes(): void
    {
        Schema::table('project',               fn (Blueprint $t) => $t->dropIndex('idx_project_timestamp'));
        Schema::table('user_deletion_requests',fn (Blueprint $t) => $t->dropIndex('idx_user_deletion_timestamp'));

        Schema::table('data_exports', function (Blueprint $table) {
            $table->dropIndex('idx_data_exports_user_status');
            $table->dropIndex('idx_data_exports_expires');
        });

        Schema::table('timetable', function (Blueprint $table) {
            $table->dropIndex('idx_timetable_user_active');
            $table->dropIndex('idx_timetable_user_day');
        });

        Schema::table('message_chat', function (Blueprint $table) {
            $table->dropIndex('idx_message_chat_list_date');
            $table->dropIndex('idx_message_chat_list_read');
        });

        Schema::table('message_actors', function (Blueprint $table) {
            $table->dropIndex('idx_message_actors_user_list');
            $table->dropIndex('idx_message_actors_list');
        });

        Schema::table('contact', function (Blueprint $table) {
            $table->dropIndex('idx_contact_user_state');
            $table->dropIndex('idx_contact_user_contact');
        });

        Schema::table('share', function (Blueprint $table) {
            $table->dropIndex('idx_share_receiving_deleted');
            $table->dropIndex('idx_share_sender_deleted');
            $table->dropIndex('idx_share_file_receiving');
        });

        Schema::table('file', function (Blueprint $table) {
            $table->dropIndex('idx_file_user_folder_state');
            $table->dropIndex('idx_file_user_type_state');
            $table->dropIndex('idx_file_bypass');
        });
    }
};
