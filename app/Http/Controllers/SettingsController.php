<?php

namespace App\Http\Controllers;

use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionPending;
use App\Models\App;
use App\Models\Contact;
use App\Models\File;
use App\Models\Timetable;
use App\Models\User;
use App\Models\UserDeletionRequest;
use App\Models\UserExpanded;
use App\Services\CryptoService;
use App\Services\KeyringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return match ($type) {
            'account'         => $this->account($request),
            'customize'       => $this->customize($request),
            'privacy'         => $this->privacy($request),
            'password'        => $this->password($request),
            'app-to-taskbar'   => $this->appToTaskbar($request),
            'reorder-taskbar'  => $this->reorderTaskbar($request),
            'erase-app-data'  => $this->eraseAppData($request),
            'theme'           => $this->theme($request),
            'regenerate-keys'  => $this->regenerateKeys(),
            'twofa-activate'   => $this->twofaActivate($request),
            'twofa-deactivate' => $this->twofaDeactivate($request),
            'request-deletion' => $this->requestDeletion($request),
            'cancel-deletion'  => $this->cancelDeletion(),
            default           => response()->json(['response' => 'error', 'text' => 'Invalid type']),
        };
    }

    protected function account(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $invalidChars = ['\\', '/', ':', '*', '?', '"', '<', '>', '|', '&'];

        $name     = str_replace($invalidChars, ' ', (string) $request->input('name', ''));
        $surname  = str_replace($invalidChars, ' ', (string) $request->input('surname', ''));
        $email    = strtolower(str_replace($invalidChars, ' ', (string) $request->input('email', '')));
        $username = strtolower(str_replace(array_merge($invalidChars, [' ', "'"]), '', (string) $request->input('username', '')));

        if ($err = $this->validateInput(
            compact('name', 'surname', 'email', 'username'),
            [
                'name'     => 'required|string|max:255',
                'surname'  => 'required|string|max:255',
                'email'    => 'required|email|max:255',
                'username' => 'required|string|max:255',
            ],
            [
                'name.required'    => __('settings-error-name-required'),
                'name.max'         => __('settings-error-name-long'),
                'surname.required' => __('settings-error-surname-required'),
                'surname.max'      => __('settings-error-surname-long'),
                'email.required'   => __('settings-error-email-required'),
                'email.email'      => __('settings-error-email-invalid'),
                'email.max'        => __('settings-error-email-long'),
                'username.required'=> __('settings-error-username-required'),
                'username.max'     => __('settings-error-username-long'),
            ]
        )) return $err;

        // Check duplicate email / username
        $conflict = User::where(fn($q) => $q->where('email', $email)->orWhere('username', $username))
            ->where('id', '!=', $userId)
            ->first(['email', 'username']);

        if ($conflict) {
            if ($conflict->email === $email)     return response()->json(['response' => 'error', 'text' => __('settings-error-email-in-use')]);
            if ($conflict->username === $username) return response()->json(['response' => 'error', 'text' => __('settings-error-username-in-use')]);
        }

        $user = auth()->user();
        UserExpanded::where('id', $userId)->update(['name' => $name, 'surname' => $surname]);
        User::where('id', $userId)->update(['username' => $username]);

        if ($user->email !== $email) {
            User::where('id', $userId)->update(['email' => $email, 'verified' => 0]);
            return response()->json(['response' => 'success', 'text' => __('settings-success-saved-verify')]);
        }

        return response()->json(['response' => 'success', 'text' => __('settings-success-saved')]);
    }

    protected function customize(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $invalidChars = ['\\', '/', ':', '*', '?', '"', '<', '>', '|', '&', ' ', "'"];

        $accent      = str_replace($invalidChars, ' ', (string) $request->input('accent', ''));
        $accent      = str_replace('#', '', $accent);
        $taskbar     = str_replace($invalidChars, ' ', (string) $request->input('taskbar', ''));
        $taskbarSize = str_replace($invalidChars, '', (string) $request->input('taskbar_size', ''));
        $bkgId       = (string) $request->input('bkg-id', '');
        $bkgOpacity  = (int) $request->input('bkg-opacity', 0);
        $bkgColor    = (string) $request->input('bkg-color', '');
        $bkgBlur     = max(0, min(20, (int) $request->input('bkg-blur', 0)));
        $ppId        = (string) $request->input('pp-id', '');

        if (strlen($accent) === 0 || strlen($accent) > 6) {
            $accent = null;
        }

        $taskbarArr     = array_values(array_filter(explode(',', $taskbar), fn($v) => is_numeric(trim($v))));
        $taskbarArr     = count($taskbarArr) > 0 ? array_map('intval', $taskbarArr) : null;
        $taskbarSizeInt = strlen($taskbarSize) > 0 ? (int) $taskbarSize : null;
        if ($taskbarSizeInt !== null && ($taskbarSizeInt > 2 || $taskbarSizeInt < 0)) {
            $taskbarSizeInt = null;
        }

        $opacityFloat = round($bkgOpacity / 100, 2);
        $rgbColor     = $this->hexToRgb($bkgColor);

        $wallpaper = $bkgId === '' ? null : [
            'id'      => (int) $bkgId,
            'opacity' => substr((string) $opacityFloat, 0, 4),
            'color'   => implode(', ', $rgbColor),
            'blur'    => $bkgBlur,
        ];

        $ppIdValue = $ppId !== '' ? (int) $ppId : null;

        UserExpanded::where('id', $userId)->update([
            'accent'          => $accent,
            'taskbar'         => $taskbarArr !== null ? json_encode($taskbarArr) : null,
            'taskbar_size'    => $taskbarSizeInt,
            'wallpaper'       => $wallpaper !== null ? json_encode($wallpaper) : null,
            'profile_picture' => $ppIdValue,
        ]);

        return response()->json(['response' => 'success', 'text' => 'Settings saved']);
    }

    protected function privacy(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $searchVisible  = (bool) $request->input('search_visible', 0);
        $showEmail      = (bool) $request->input('show_email', 0);
        $showUsername   = (bool) $request->input('show_username', 0);
        $sendMessages   = (int)  $request->input('send_messages', 0);
        $shareDocuments = (int)  $request->input('share_documents', 0);
        $msOffice       = (int)  $request->input('ms_office', 0);

        UserExpanded::where('id', $userId)->update([
            'privacy_search_visible'  => $searchVisible,
            'privacy_show_email'      => $showEmail,
            'privacy_show_username'   => $showUsername,
            'privacy_send_messages'   => $sendMessages,
            'privacy_share_documents' => $shareDocuments,
            'privacy_ms_office'       => $msOffice,
        ]);

        return response()->json(['response' => 'success', 'text' => 'Settings saved']);
    }

    protected function password(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            $request->only(['old', 'new', 'new-2']),
            [
                'old'   => 'required|string',
                'new'   => 'required|string',
                'new-2' => 'required|same:new',
            ],
            [
                'old.required'   => __('settings-error-old-password-required'),
                'new.required'   => __('settings-error-new-password-required'),
                'new-2.required' => __('settings-error-new-password-repeat'),
                'new-2.same'     => __('settings-error-passwords-no-match'),
            ]
        )) return $err;

        $user = auth()->user();

        if (!Hash::check($request->input('old'), $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('settings-error-password-incorrect')]);
        }

        User::where('id', $user->id)->update(['password' => Hash::make($request->input('new'))]);
        UserExpanded::where('id', $user->id)->update(['password_last_change' => now()]);

        return response()->json(['response' => 'success', 'text' => __('settings-success-password')]);
    }

    protected function appToTaskbar(Request $request): JsonResponse
    {
        $userId  = auth()->id();
        $appName = trim((string) $request->query('app', ''));

        if ($appName === '') {
            return response()->json(['response' => 'error', 'text' => 'Invalid app']);
        }

        $app = App::where('unique_name', $appName)->first(['id']);
        if (!$app) {
            return response()->json(['response' => 'error', 'text' => 'App not found']);
        }

        $appId    = $app->id;
        $expanded = UserExpanded::find($userId);
        $taskbar  = array_values(array_map('intval', $expanded->taskbar ?? []));
        $key      = array_search($appId, $taskbar);

        if ($key !== false) {
            unset($taskbar[$key]);
            $operation = 'removed';
        } else {
            $taskbar[] = $appId;
            $operation = 'added';
        }

        $expanded->update(['taskbar' => array_values($taskbar)]);

        return response()->json(['response' => 'success', 'text' => $operation]);
    }

    protected function reorderTaskbar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user     = Auth::user();
        $expanded = UserExpanded::find($user->id);
        $current  = array_values(array_map('intval', $expanded->taskbar ?? []));

        $ids = array_values(array_filter(
            array_map('intval', explode(',', (string) $request->input('taskbar', ''))),
            fn($id) => $id > 0
        ));

        // Only accept IDs already in the user's taskbar — no additions or removals
        $allowed = array_flip($current);
        $ids     = array_values(array_filter($ids, fn($id) => isset($allowed[$id])));

        if (count($ids) !== count($current)) {
            return response()->json(['response' => 'error', 'text' => 'Invalid taskbar order']);
        }

        $expanded->update(['taskbar' => $ids]);

        return response()->json(['response' => 'success', 'text' => 'Taskbar reordered']);
    }

    protected function eraseAppData(Request $request): JsonResponse
    {
        $userId  = auth()->id();
        $appName = trim((string) $request->query('app', ''));

        if ($appName === '') {
            return response()->json(['response' => 'error', 'text' => 'Invalid app']);
        }

        switch ($appName) {
            case 'timetable':
                Timetable::where('user', $userId)->delete();
                break;
            case 'diary':
                File::where('user_id', $userId)->ofType('diary')->delete();
                break;
            case 'contact':
                Contact::where('user_id', $userId)->delete();
                break;
            case 'file-manager':
                File::where('user_id', $userId)
                    ->whereNotIn('type', ['diary', 'notebook'])
                    ->delete();
                break;
            case 'writer':
                File::where('user_id', $userId)->ofType('notebook')->delete();
                break;
            default:
                return response()->json(['response' => 'error', 'text' => 'Invalid app']);
        }

        return response()->json(['response' => 'success', 'text' => 'App data erased']);
    }

    protected function theme(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $raw    = $request->query('app') ?? $request->query('theme');

        if ($err = $this->validateInput(
            ['theme' => $raw],
            ['theme' => 'required|string'],
            ['theme.*' => __('store-error-theme-required')]
        )) return $err;

        $raw = trim((string) $raw);

        // "t-default" means remove theme; strip "t-" prefix used in legacy calls
        $themeValue = ($raw === 't-default') ? null : str_replace('t-', '', $raw);

        UserExpanded::where('id', $userId)->update(['theme' => $themeValue]);

        return response()->json(['response' => 'success', 'text' => __('settings-success-saved')]);
    }

    protected function regenerateKeys(): JsonResponse
    {
        $keyring = app(KeyringService::class);
        $keyring->generateEd25519KeyPair(auth()->id(), force: true);
        return response()->json(['response' => 'success', 'text' => 'Chiavi crittografiche generate con successo.']);
    }

    protected function twofaActivate(Request $request): JsonResponse
    {
        $secret = $request->session()->get('twofa_secret');
        if (!$secret) {
            return response()->json(['response' => 'error', 'text' => __('twofa-session-expired')]);
        }

        if ($err = $this->validateInput(
            ['password' => $request->input('password')],
            ['password' => 'required|string'],
            ['password.*' => __('password-required')]
        )) return $err;

        $user = auth()->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('twofa-error2')]);
        }

        $tfa = new \RobThree\Auth\TwoFactorAuth(
            new \RobThree\Auth\Providers\Qr\QRServerProvider()
        );

        if (!$tfa->verifyCode($secret, trim((string) $request->input('token', '')))) {
            return response()->json(['response' => 'error', 'text' => __('twofa-error')]);
        }

        $encrypted = app(CryptoService::class)->encryptTwofa($secret, $user->id);

        UserExpanded::where('id', $user->id)->update(['twofa' => $encrypted]);
        $request->session()->forget('twofa_secret');

        return response()->json([
            'response' => 'success',
            'header'   => __('twofa-header'),
            'text'     => __('twofa-text'),
        ]);
    }

    protected function twofaDeactivate(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            ['password' => $request->input('password')],
            ['password' => 'required|string'],
            ['password.*' => __('password-required')]
        )) return $err;

        $user = auth()->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('twofa-error2')]);
        }

        UserExpanded::where('id', $user->id)->update(['twofa' => null]);

        return response()->json(['response' => 'success', 'text' => __('twofa-deactivated')]);
    }

    protected function requestDeletion(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            ['password' => $request->input('password')],
            ['password' => 'required|string'],
            ['password.*' => __('settings-error-delete-password-required')]
        )) return $err;

        $user = auth()->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('settings-error-delete-password-incorrect')]);
        }

        if (UserDeletionRequest::where('user_id', $user->id)->exists()) {
            return response()->json(['response' => 'error', 'text' => __('settings-error-delete-already-pending')]);
        }

        $deletionAt = now()->addDays(30);

        UserDeletionRequest::create([
            'user_id'           => $user->id,
            'deletion_timestamp' => $deletionAt,
        ]);

        $locale = $user->expanded->language ?? config('app.locale', 'en');
        Mail::to($user->email)->locale($locale)->send(new AccountDeletionPending(
            deletionDate: $deletionAt->format('d/m/Y'),
            loginUrl: route('auth.login.page'),
        ));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'response'     => 'success',
            'text'         => __('settings-success-delete-requested'),
            'redirect-url' => route('auth.login.page'),
        ]);
    }

    protected function cancelDeletion(): JsonResponse
    {
        $userId  = auth()->id();
        $deleted = UserDeletionRequest::where('user_id', $userId)->delete();

        if (!$deleted) {
            return response()->json(['response' => 'error', 'text' => __('settings-error-delete-already-pending')]);
        }

        return response()->json(['response' => 'success', 'text' => __('settings-success-delete-cancelled')]);
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
