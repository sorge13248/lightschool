<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Support\Config;

class PasskeyLoginController extends Controller
{
    /** POST /api/passkeys/authenticate */
    public function authenticate(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['response' => 'error', 'text' => __('login-already-logged-in')], 403);
        }

        $request->validate([
            'start_authentication_response' => ['required', 'json'],
        ]);

        /** @var FindPasskeyToAuthenticateAction $action */
        $action = Config::getAction('find_passkey', FindPasskeyToAuthenticateAction::class);

        $passkeyOptions = Session::get('passkey-authentication-options');

        if (! $passkeyOptions) {
            return response()->json(['response' => 'error', 'text' => __('passkey-session-expired')]);
        }

        try {
            $passkey = $action->execute(
                $request->input('start_authentication_response'),
                $passkeyOptions,
            );
        } catch (\Throwable $e) {
            Log::warning('Passkey authentication failed', ['error' => $e->getMessage()]);
            return response()->json(['response' => 'error', 'text' => __('passkey-invalid')]);
        }

        if (! $passkey) {
            return response()->json(['response' => 'error', 'text' => __('passkey-invalid')]);
        }

        /** @var User|null $user */
        $user = $passkey->authenticatable;

        if (! $user) {
            return response()->json(['response' => 'error', 'text' => __('passkey-invalid')]);
        }

        if (! $user->verified) {
            return response()->json(['response' => 'error', 'text' => __('login-error5')]);
        }

        if ($user->status !== 0) {
            return response()->json(['response' => 'error', 'text' => __('login-suspended')]);
        }

        // Passkey authentication bypasses 2FA — the device-bound credential itself
        // proves possession of the registered authenticator.
        Auth::loginUsingId($user->id);

        $user = User::find($user->id);
        $user->update(['last_login' => time()]);
        $user->logAccess($request);
        $user->cancelPendingDeletion();

        $request->session()->regenerate();

        return response()->json([
            'response'     => 'success',
            'text'         => __('login-success2'),
            'redirect-url' => route('my.dashboard'),
        ]);
    }
}
