<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserExpanded;
use App\Services\CryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class LoginController extends Controller
{
    public function __construct(protected CryptoService $crypto) {}

    /** POST /auth/login */
    public function login(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['response' => 'error', 'text' => __('login-already-logged-in')], 403);
        }

        // ── 2FA second factor ──────────────────────────────────────────────
        if ($request->query('2fa') === 'true' || $request->input('2fa') === 'true') {
            return $this->verifyTwoFactor($request);
        }

        // ── Standard login ─────────────────────────────────────────────────
        if ($err = $this->validateInput(
            ['username' => $request->input('username'), 'password' => $request->input('password')],
            ['username' => 'required|string|max:128', 'password' => 'required|string|max:128'],
            ['username.*' => __('login-error'), 'password.*' => __('login-error')]
        )) return $err;

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('login-error2')]);
        }

        if (!$user->verified) {
            return response()->json(['response' => 'error', 'text' => __('login-error5')]);
        }

        if ($user->status !== 0) {
            return response()->json(['response' => 'error', 'text' => __('login-suspended')]);
        }

        // Check 2FA — store the password in the session so step 2 can re-verify
        // that the OTP submitter is the same person who passed the password check.
        // The session is already encrypted server-side by Laravel (APP_KEY), so
        // no additional asymmetric wrapping is needed here.
        $expanded = $user->expanded;
        if ($expanded && $expanded->twofa !== null) {
            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_username', $username);
            $request->session()->put('2fa_password', $password);
            return response()->json(['response' => '2fa']);
        }

        Auth::loginUsingId($user->id);
        $user->update(['last_login' => time()]);
        $user->logAccess($request);
        $user->cancelPendingDeletion();

        $request->session()->regenerate();

        return response()->json(['response' => 'success', 'text' => __('login-success2'), 'redirect-url' => route('my.dashboard')]);
    }

    /** POST /auth/login?2fa=true — verify TOTP and complete login */
    protected function verifyTwoFactor(Request $request): JsonResponse
    {
        $userId   = $request->session()->get('2fa_user_id');
        $password = $request->session()->get('2fa_password');

        if (!$userId || !$password) {
            return response()->json(['response' => 'error', 'text' => __('login-session-expired')]);
        }

        if ($err = $this->validateInput(
            ['token' => $request->input('token')],
            ['token' => 'required|string'],
            ['token.*' => __('login-error3-otp')]
        )) return $err;

        $expanded = UserExpanded::find($userId);
        if (!$expanded || $expanded->twofa === null) {
            return response()->json(['response' => 'error', 'text' => __('login-error-2fa-not-configured')]);
        }

        try {
            $secret = $this->crypto->decryptTwofa($expanded->twofa, $userId);
        } catch (\Throwable $e) {
            Log::error('2FA secret decryption failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json(['response' => 'error', 'text' => __('login-error2-otp')]);
        }

        $user = User::find($userId);
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['response' => 'error', 'text' => __('login-error2')]);
        }

        $tfa = new TwoFactorAuth(new QRServerProvider(), 'LightSchool');
        if (!$tfa->verifyCode($secret, trim((string) $request->input('token')))) {
            return response()->json(['response' => 'error', 'text' => __('login-error-otp')]);
        }

        $request->session()->forget(['2fa_user_id', '2fa_username', '2fa_password']);
        Auth::loginUsingId($userId);

        $user = User::find($userId);
        $user->update(['last_login' => time()]);
        $user->logAccess($request);
        $user->cancelPendingDeletion();

        $request->session()->regenerate();

        return response()->json(['response' => 'success', 'text' => __('login-success'), 'redirect-url' => route('my.dashboard')]);
    }
}
