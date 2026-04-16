<?php

namespace App\Console\Commands;

use App\Models\DataExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredDataExports extends Command
{
    protected $signature   = 'ls:clean-exports';
    protected $description = 'Delete zip files and records for expired or already-downloaded data exports';

    public function handle(): int
    {
        // Candidates: expired by time, or already downloaded
        $stale = DataExport::where(function ($q) {
            $q->where('expires_at', '<=', now())
              ->orWhere('status', 'downloaded');
        })->get();

        $cleaned = 0;

        foreach ($stale as $export) {
            if ($export->zip_path && Storage::disk('local')->exists($export->zip_path)) {
                Storage::disk('local')->delete($export->zip_path);
            }

            $export->delete();
            $cleaned++;
        }

        $this->info("Cleaned {$cleaned} expired export(s).");

        return self::SUCCESS;
    }
}
