<?php

namespace App\Console\Commands;

use App\Mail\AccountDeleted;
use App\Models\UserDeletionRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPasskeys\Models\Passkey;

class ProcessUserDeletions extends Command
{
    protected $signature = 'ls:process-user-deletions';
    protected $description = 'Delete accounts whose 30-day deletion window has expired';

    public function handle(): int
    {
        $due = UserDeletionRequest::where('deletion_timestamp', '<=', now())->get();

        foreach ($due as $request) {
            $this->deleteUser($request->user_id);
        }

        $this->info("Processed {$due->count()} deletion(s).");

        return self::SUCCESS;
    }

    protected function deleteUser(int $userId): void
    {
        $email  = DB::table('users')->where('id', $userId)->value('email');
        $locale = DB::table('users_expanded')->where('id', $userId)->value('language') ?? config('app.locale', 'en');

        // Delete the user's entire uploads directory
        $uploadsDir = md5((string) $userId);
        if (Storage::disk('uploads')->exists($uploadsDir)) {
            Storage::disk('uploads')->deleteDirectory($uploadsDir);
        }

        // Delete keyring from secure dir
        $keyringPath = rtrim(config('lightschool.secure_dir'), '/') . '/keyring/' . $userId;
        if (is_dir($keyringPath)) {
            $this->deleteDirectory($keyringPath);
        }

        // Delete all user-related DB records in dependency order
        DB::transaction(function () use ($userId) {
            // Messages: remove user's participation and orphaned lists
            $listIds = DB::table('message_actors')->where('user_id', $userId)->pluck('list_id');

            DB::table('message_chat')->where('sender', $userId)->delete();
            DB::table('message_actors')->where('user_id', $userId)->delete();

            if ($listIds->isNotEmpty()) {
                $orphanedListIds = DB::table('message_list')
                    ->whereIn('id', $listIds)
                    ->whereNotIn('id', DB::table('message_actors')->select('list_id'))
                    ->pluck('id');

                if ($orphanedListIds->isNotEmpty()) {
                    DB::table('message_chat')->whereIn('message_list_id', $orphanedListIds)->delete();
                    DB::table('message_list')->whereIn('id', $orphanedListIds)->delete();
                }
            }

            DB::table('share')->where('sender', $userId)->orWhere('receiving', $userId)->delete();
            DB::table('contact')->where('user_id', $userId)->delete();
            DB::table('timetable')->where('user', $userId)->delete();
            DB::table('file')->where('user_id', $userId)->delete();
            DB::table('project_files')->where('user', $userId)->delete();
            DB::table('access')->where('user', $userId)->delete();
            DB::table('users_confirmations')->where('user_id', $userId)->delete();
            DB::table('users_resets')->where('user', $userId)->delete();
            DB::table('user_deletion_requests')->where('user_id', $userId)->delete();
            Passkey::where('authenticatable_id', $userId)->delete();
            DB::table('users_expanded')->where('id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        });

        if ($email) {
            Mail::to($email)->locale($locale)->send(new AccountDeleted());
        }

        $this->line("  Deleted user #{$userId}");
    }

    protected function deleteDirectory(string $path): void
    {
        foreach (array_diff(scandir($path), ['.', '..']) as $entry) {
            $full = "$path/$entry";
            is_dir($full) ? $this->deleteDirectory($full) : unlink($full);
        }
        rmdir($path);
    }
}
