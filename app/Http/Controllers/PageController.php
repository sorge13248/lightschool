<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\DataExport;
use App\Models\File;
use App\Models\Theme;
use App\Models\UserDeletionRequest;
use App\Models\UserExpanded;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageController extends Controller
{
    // ─── Public landing pages ────────────────────────────────────────────────

    /** GET / */
    public function home(): InertiaResponse
    {
        return Inertia::render('public/Home');
    }

    /** GET /overview */
    public function overview(): InertiaResponse
    {
        return Inertia::render('public/Overview');
    }

    /** GET /features */
    public function features(): InertiaResponse
    {
        return Inertia::render('public/Features');
    }

    /** GET /privacy */
    public function privacy(): InertiaResponse
    {
        return Inertia::render('public/Privacy');
    }

    /** GET /tos */
    public function tos(): InertiaResponse
    {
        return Inertia::render('public/Tos');
    }

    /** GET /cookie */
    public function cookie(): InertiaResponse
    {
        return Inertia::render('public/Cookie');
    }

    /** GET /language */
    public function languagePage(Request $request): InertiaResponse
    {
        $redirect  = $request->query('redirect', url('/'));
        $languages = [];

        foreach (glob(lang_path('*.json')) as $path) {
            $code = pathinfo($path, PATHINFO_FILENAME);
            $data = json_decode(file_get_contents($path), true) ?? [];
            $languages[$code] = [
                'LANG_INT_NAME' => $data['LANG_INT_NAME'] ?? $code,
                'LANG_NAME'     => $data['LANG_NAME']     ?? '',
            ];
        }
        ksort($languages);

        return Inertia::render('public/LanguageSelector', compact('redirect', 'languages'));
    }

    /** GET /language/set */
    public function setLanguage(Request $request): RedirectResponse
    {
        $lang     = $request->query('lang', 'en');
        $redirect = $request->query('redirect', url('/'));

        if (!file_exists(lang_path("{$lang}.json"))) {
            $lang = 'en';
        }

        // Reject external redirects to prevent open-redirect phishing
        if (!str_starts_with($redirect, url('/'))) {
            $redirect = url('/');
        }

        return redirect($redirect)->withCookie(
            cookie()->forever('language', $lang, '/', null, false, false) // httpOnly=false so JS can read it
        );
    }

    // ─── Auth pages ──────────────────────────────────────────────────────────

    /** GET /my — Dashboard for logged-in users, login page for guests */
    public function dashboard(): InertiaResponse|RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('auth.login.page');
        }

        $userId = auth()->id();

        // Random quote
        $quotes = __('quotes');
        $quote  = is_array($quotes) && !empty($quotes) ? $quotes[array_rand($quotes)] : null;

        // Desktop files (top 10) — fav=1 items
        $desktop = File::where('user_id', $userId)
            ->favorites()->active()
            ->whereNull('history')
            ->orderByDesc('last_view')
            ->limit(10)
            ->get(['id', 'name', 'type', 'file_url', 'file_type', 'icon']);

        // Upcoming diary events (top 10)
        $diaryEvents = File::where('user_id', $userId)
            ->active()
            ->ofType('diary')
            ->whereDate('diary_date', '>=', now())
            ->orderBy('diary_date')
            ->limit(10)
            ->get(['id', 'name', 'diary_type', 'diary_date', 'diary_priority']);

        // Tomorrow's day name
        $tomorrow = (new \DateTime('tomorrow'))->format('N'); // 1=Mon … 7=Sun
        $weekDays = __('week-days');
        $tomorrowDayName = is_array($weekDays) ? ($weekDays[$tomorrow] ?? '') : '';

        return Inertia::render('app/Dashboard', [
            'quote'           => $quote,
            'desktop'         => $desktop->map(fn($f) => [
                'id'        => $f->id,
                'name'      => $f->name,
                'type'      => $f->type,
                'file_type' => $f->file_type,
                'icon'      => $f->icon,
            ])->values()->toArray(),
            'diaryEvents'     => $diaryEvents->map(fn($e) => [
                'id'             => $e->id,
                'name'           => $e->name,
                'diary_type'     => $e->diary_type,
                'diary_date'     => $e->diary_date,
                'diary_priority' => $e->diary_priority,
            ])->values()->toArray(),
            'tomorrowDayName' => $tomorrowDayName,
        ]);
    }

    /** GET /my/app/file-manager */
    public function fileManager(Request $request): InertiaResponse
    {
        $folder        = $request->query('folder');
        $currentFolder = null;
        $tree          = [];

        if (is_numeric($folder)) {
            $userId = auth()->id();
            $currentFolder = File::where('id', (int) $folder)
                ->where('user_id', $userId)
                ->ofType('folder')
                ->whereNull('deleted_at')
                ->first(['id', 'name', 'icon', 'folder', 'trash']);

            if ($currentFolder) {
                $parentId = $currentFolder->folder;
                while (is_numeric($parentId)) {
                    $parent = File::where('id', (int) $parentId)
                        ->where('user_id', $userId)
                        ->ofType('folder')
                        ->whereNull('deleted_at')
                        ->first(['id', 'name', 'icon', 'folder']);
                    if (!$parent) break;
                    $tree[]   = ['id' => $parent->id, 'name' => $parent->name, 'icon' => $parent->icon];
                    $parentId = $parent->folder;
                }
                $currentFolder = $currentFolder->toArray();
            }
        }

        $expanded  = auth()->user()->expanded;
        $diskSpace = ($expanded?->plan->disk_space ?? 100);

        return Inertia::render('app/FileManager', [
            'folder'        => $folder,
            'currentFolder' => $currentFolder,
            'tree'          => $tree,
            'diskSpace'     => $diskSpace,
            'owner'         => $request->query('owner', ''),
        ]);
    }

    /** GET /my/app/contact */
    public function contact(): InertiaResponse
    {
        return Inertia::render('app/Contact');
    }

    /** GET /my/app/diary */
    public function diary(): InertiaResponse
    {
        return Inertia::render('app/Diary');
    }

    /** GET /my/app/message */
    public function message(): InertiaResponse
    {
        return Inertia::render('app/Message', [
            'openUsername' => request()->query('username', ''),
            'openAttach'   => request()->query('attach', ''),
        ]);
    }

    /** GET /my/app/settings */
    public function settings(Request $request): InertiaResponse
    {
        $userId     = auth()->id();
        $usedBytes  = $this->getUserDiskUsage($userId);
        $expanded   = UserExpanded::with('plan')->find($userId);
        $totalBytes = ($expanded?->plan->disk_space ?? 100) * 1024 * 1024;

        return Inertia::render('app/Settings', [
            'diskSpaceUsed'  => $usedBytes,
            'diskSpaceTotal' => $totalBytes,
        ]);
    }

    /** GET /my/app/settings/account */
    public function settingsAccount(): InertiaResponse
    {
        $user = auth()->user();

        return Inertia::render('app/SettingsAccount', [
            'email'    => $user->email    ?? '',
            'username' => $user->username ?? '',
        ]);
    }

    /** GET /my/app/settings/language */
    public function settingsLanguage(Request $request): InertiaResponse
    {
        $languages = [];
        foreach (glob(lang_path('*.json')) as $path) {
            $code = pathinfo($path, PATHINFO_FILENAME);
            $data = json_decode(file_get_contents($path), true) ?? [];
            $languages[$code] = [
                'LANG_INT_NAME' => $data['LANG_INT_NAME'] ?? $code,
                'LANG_NAME'     => $data['LANG_NAME']     ?? '',
            ];
        }
        ksort($languages);

        return Inertia::render('app/SettingsLanguage', [
            'languages'       => $languages,
            'currentLanguage' => app()->getLocale(),
            'currentUrl'      => $request->fullUrl(),
        ]);
    }

    /** GET /my/app/settings/app */
    public function settingsApp(): InertiaResponse
    {
        $apps = App::orderBy('unique_name')
            ->select('id', 'unique_name')
            ->get()
            ->toArray();

        return Inertia::render('app/SettingsApp', [
            'apps' => $apps,
        ]);
    }

    /** GET /my/app/settings/customize */
    public function settingsCustomize(): InertiaResponse
    {
        $user     = auth()->user();
        $expanded = $user->expanded;

        $wallpaper = $expanded->wallpaper ?: null;

        $profilePicId = $expanded->profile_picture ? (int) $expanded->profile_picture : null;

        $taskbarIds   = $expanded->getTaskbarArray();
        $taskbarApps  = App::whereIn('id', $taskbarIds)->select('id', 'unique_name')->get()->keyBy('id');
        $taskbarItems = collect($taskbarIds)
            ->map(fn($id) => $taskbarApps->get($id))
            ->filter()
            ->map(fn($app) => ['id' => $app->id, 'unique_name' => $app->unique_name])
            ->values()
            ->toArray();

        return Inertia::render('app/SettingsCustomize', [
            'profilePicId' => $profilePicId,
            'wallpaper'    => $wallpaper,
            'taskbarItems' => $taskbarItems,
        ]);
    }

    /** GET /my/app/settings/security */
    public function settingsSecurity(): InertiaResponse
    {
        $user     = auth()->user();
        $expanded = $user->expanded;

        $privacy = [
            'search_visible'  => (int) ($expanded->privacy_search_visible  ?? 1),
            'show_email'      => (int) ($expanded->privacy_show_email      ?? 0),
            'show_username'   => (int) ($expanded->privacy_show_username   ?? 0),
            'send_messages'   => (int) ($expanded->privacy_send_messages   ?? 2),
            'share_documents' => (int) ($expanded->privacy_share_documents ?? 2),
            'ms_office'       => (int) ($expanded->privacy_ms_office       ?? 1),
        ];

        $hasTwofa           = !empty($expanded->twofa);
        $passwordLastChange = $expanded->password_last_change
            ? \Carbon\Carbon::parse($expanded->password_last_change)->toISOString()
            : null;

        $keysOk = false;
        try {
            $keyring   = app(\App\Services\KeyringService::class);
            $keyring->getEd25519PublicKey($user->id);
            $keyring->getEd25519SecretKey($user->id);
            $crypto    = app(\App\Services\CryptoService::class);
            $plainText = \Illuminate\Support\Str::random(32);
            $encrypted = $crypto->encrypt($plainText, $user->id);
            $keysOk    = ($plainText === $crypto->decrypt($encrypted['data'], $encrypted['key'], $user->id));
        } catch (\Exception $e) {
            \Log::error($e);
            $keysOk = false;
        }

        return Inertia::render('app/SettingsSecurity', [
            'keysOk'             => $keysOk,
            'hasTwofa'           => $hasTwofa,
            'passwordLastChange' => $passwordLastChange,
            'exportDataUrl'      => route('app.settings.export-data'),
            'privacy'            => $privacy,
        ]);
    }

    /** GET /my/app/settings/password */
    public function settingsPassword(): InertiaResponse
    {
        return Inertia::render('app/SettingsPassword');
    }

    /** GET /my/app/settings/delete-account */
    public function settingsDeleteAccount(): InertiaResponse
    {
        $pendingRequest = UserDeletionRequest::where('user_id', auth()->id())->first();

        return Inertia::render('app/SettingsDeleteAccount', [
            'pendingRequest' => $pendingRequest
                ? ['deletion_timestamp' => $pendingRequest->deletion_timestamp]
                : null,
            'exportDataUrl' => route('app.settings.export-data'),
        ]);
    }

    /** GET /my/app/settings/export-data */
    public function settingsExportData(): InertiaResponse
    {
        $activeExport = DataExport::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->where('expires_at', '>', now())
            ->first();

        return Inertia::render('app/SettingsExportData', [
            'activeExport' => $activeExport
                ? [
                    'status'     => $activeExport->status,
                    'expires_at' => $activeExport->expires_at,
                    'token'      => $activeExport->token,
                ]
                : null,
            'downloadRoute' => url('/my/export/download/__TOKEN__'),
            'requestRoute'  => route('export.request'),
        ]);
    }

    /** GET /my/app/settings/2fa */
    public function settingsTwofa(Request $request): InertiaResponse
    {
        $user     = auth()->user();
        $expanded = $user->expanded;
        $hasTwofa = !empty($expanded->twofa);

        $qrUri  = null;
        $secret = null;

        if (!$hasTwofa) {
            $tfa    = new \RobThree\Auth\TwoFactorAuth(
                new \RobThree\Auth\Providers\Qr\QRServerProvider()
            );
            $secret = $tfa->createSecret();
            $request->session()->put('twofa_secret', $secret);

            $label = 'LightSchool - ' . ($expanded->name ?? '') . ' ' . ($expanded->surname ?? '');
            $qrUri = $tfa->getQRCodeImageAsDataUri(trim($label), $secret);
        }

        return Inertia::render('app/SettingsTwoFa', [
            'hasTwofa' => $hasTwofa,
            'qrUri'    => $qrUri,
            'secret'   => $secret,
        ]);
    }

    /** GET /my/app/settings/passkeys */
    public function settingsPasskeys(): InertiaResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $passkeys = $user->passkeys()
            ->orderBy('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'last_used_at' => $p->last_used_at?->toISOString(),
                'created_at'   => $p->created_at->toISOString(),
            ])->values()->toArray();

        return Inertia::render('app/SettingsPasskeys', [
            'passkeys' => $passkeys,
        ]);
    }

    // ─── Disk space helper ───────────────────────────────────────────────────

    private function getUserDiskUsage(int $userId): int
    {
        $userDir = public_path('img/' . md5((string) $userId) . '/');
        if (!is_dir($userDir)) {
            return 0;
        }
        $bytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($userDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                $bytes += $file->getSize();
            }
        } catch (\Exception $e) {
            // ignore unreadable dirs
        }
        return $bytes;
    }

    /** GET /my/app/share */
    public function share(): InertiaResponse
    {
        return Inertia::render('app/Share');
    }

    /** GET /my/app/timetable */
    public function timetable(): InertiaResponse
    {
        return Inertia::render('app/Timetable');
    }

    /** GET /my/app/trash */
    public function trash(): InertiaResponse
    {
        return Inertia::render('app/Trash');
    }

    /** GET /my/app/reader/{type}/{id} */
    public function reader(Request $request, string $type, int $id): InertiaResponse
    {
        $allowedTypes = ['notebook', 'file', 'diary', 'contact'];
        if (!in_array($type, $allowedTypes, true)) {
            abort(404);
        }
        $msOffice = (int) (auth()->user()->expanded->privacy_ms_office ?? 0);
        return Inertia::render('app/Reader', [
            'type'     => $type,
            'fileId'   => $id,
            'msOffice' => $msOffice,
        ]);
    }

    /** GET /my/app/writer/{folder?} */
    public function writer(Request $request, ?string $folder = null): InertiaResponse
    {
        $folderId = (int) ($folder ?? 0);
        return Inertia::render('app/Writer', [
            'fileId'   => $request->query('id') ? (int) $request->query('id') : null,
            'folderId' => $folderId,
        ]);
    }

    /** GET /my/app/project */
    public function project(): InertiaResponse
    {
        return Inertia::render('app/Project');
    }

    /** GET /my/register */
    public function registerPage(): InertiaResponse
    {
        return Inertia::render('auth/Register');
    }

    /** GET /my/password or /auth/password */
    public function passwordPage(Request $request): InertiaResponse
    {
        $selector = $request->query('selector');
        $token    = $request->query('token');

        return Inertia::render('auth/PasswordRecovery', [
            'resetToken' => $selector && $token,
            'selector'   => $selector ?? null,
            'token'      => $token ?? null,
        ]);
    }

    /** GET /auth/login */
    public function loginPage(): InertiaResponse
    {
        if (auth()->check()) {
            return Inertia::render('app/FileManager');
        }
        return Inertia::render('auth/Login');
    }

    /** GET /auth/otp */
    public function otpPage(): InertiaResponse
    {
        return Inertia::render('auth/Otp');
    }

    /** GET /img/icon/{type}/{name} — theme-aware icon serving */
    public function icon(Request $request, string $type, string $name): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $color = 'black';
        if (auth()->check()) {
            $themeKey = auth()->user()->expanded?->theme;
            if ($themeKey) {
                $themeIcon = Theme::where('unique_name', $themeKey)->value('icon');
                if ($themeIcon) {
                    $color = $themeIcon;
                }
            }
        }

        if ($type === 'common') {
            $file = public_path("img/color/{$name}.png");
        } elseif ($type === 'app') {
            $file = public_path("img/app-icons/{$name}/{$color}/icon.png");
        } else {
            abort(404);
        }

        if (!file_exists($file)) {
            abort(404);
        }

        return response()->file($file, ['Content-Type' => 'image/png']);
    }

    /** GET /404 */
    public function notFound(): \Illuminate\Http\Response
    {
        return response(view('errors.404'), 404);
    }
}
