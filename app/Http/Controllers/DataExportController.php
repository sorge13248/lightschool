<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserDataJob;
use App\Models\DataExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataExportController extends Controller
{
    /**
     * POST /api/export/request
     * Queue a new data export for the authenticated user.
     */
    public function request(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Enforce one active export at a time
        $hasActive = DataExport::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->where('expires_at', '>', now())
            ->exists();

        if ($hasActive) {
            return response()->json([
                'response' => 'error',
                'text'     => __('export-already-pending'),
            ]);
        }

        $export = DataExport::create([
            'user_id'      => $user->id,
            'token'        => bin2hex(random_bytes(32)),
            'status'       => 'pending',
            'ip_address'   => $request->ip(),
            'requested_at' => now(),
            'expires_at'   => now()->addDays(7),
        ]);

        ExportUserDataJob::dispatch($export->id);

        return response()->json([
            'response' => 'success',
            'text'     => __('export-requested'),
        ]);
    }

    /**
     * GET /my/export/{token}
     * Show the download page (status + password form).
     */
    public function showDownload(string $token): InertiaResponse
    {
        $export = DataExport::where('token', $token)->firstOrFail();

        if ($export->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('app/ExportDownload', [
            'token'     => $export->token,
            'status'    => $export->status,
            'isExpired' => $export->isExpired(),
            'expiresAt' => $export->expires_at?->toISOString(),
            'error'     => session('export_error'),
        ]);
    }

    /**
     * POST /my/export/{token}
     * Verify password and stream the zip file.
     */
    public function download(Request $request, string $token): BinaryFileResponse|RedirectResponse
    {
        $export = DataExport::where('token', $token)->firstOrFail();

        if ($export->user_id !== auth()->id()) {
            abort(403);
        }

        if ($export->isExpired() || !in_array($export->status, ['ready'], true)) {
            return back()->with('export_error', __('export-not-available'));
        }

        if (!Hash::check($request->input('password', ''), auth()->user()->password)) {
            return back()->with('export_error', __('export-wrong-password'));
        }

        if (!Storage::disk('local')->exists($export->zip_path)) {
            return back()->with('export_error', __('export-file-not-found'));
        }

        $path = Storage::disk('local')->path($export->zip_path);

        $export->update([
            'status'        => 'downloaded',
            'downloaded_at' => now(),
        ]);

        return response()
            ->download($path, 'lightschool-export.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
