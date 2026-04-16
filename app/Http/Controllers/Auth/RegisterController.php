<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\UserConfirmation;
use App\Models\UserExpanded;
use App\Services\KeyringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function __construct(protected KeyringService $keyring) {}

    /** POST /auth/register */
    public function register(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['response' => 'error', 'text' => __('register-error-already-logged-in')], 403);
        }

        $name      = trim(preg_replace('/[\\\\\\/:*?"<>|&]/', '', (string) $request->input('name', '')));
        $surname   = trim(preg_replace('/[\\\\\\/:*?"<>|&]/', '', (string) $request->input('surname', '')));
        $email     = trim((string) $request->input('email', ''));
        $username  = preg_replace('/[\\\\\\/:*?"<>|&\s]/', '', (string) $request->input('username', ''));
        $password  = (string) $request->input('password', '');
        $password2 = (string) $request->input('password-2', '');

        $v = Validator::make(
            compact('name', 'surname', 'email', 'username', 'password') + ['password-2' => $password2],
            [
                'name'      => 'required|string|max:255',
                'surname'   => 'required|string|max:255',
                'email'     => 'required|email|max:255',
                'username'  => 'required|string|min:4|max:255',
                'password'  => ['required', 'string', 'min:8', Rule::notIn([
                    'password', '12345678', 'qwerty', 'azerty', 'qwertz',
                    'helloooo', '00000000', '11111111',
                ])],
                'password-2' => 'required|same:password',
            ],
            [
                'name.required'      => __('register-error-name'),
                'surname.required'   => __('register-error-surname'),
                'email.required'     => __('register-error-email'),
                'email.email'        => __('register-error-email'),
                'username.required'  => __('register-error-username'),
                'username.min'       => __('register-error-username-short'),
                'password.required'  => __('register-error-password'),
                'password.min'       => __('register-error-password'),
                'password.not_in'    => __('register-error-password'),
                'password-2.same'    => __('register-error-passwords-match'),
            ]
        );

        if ($v->fails()) {
            return response()->json(['response' => 'error', 'text' => implode(' ', $v->errors()->all())]);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['response' => 'error', 'text' => __('register-error-email-taken')]);
        }
        if (User::where('username', $username)->exists()) {
            return response()->json(['response' => 'error', 'text' => __('register-error-username-taken')]);
        }

        $user = User::create([
            'email'      => $email,
            'password'   => Hash::make($password),
            'username'   => $username,
            'status'     => 0,
            'verified'   => 0,
            'resettable' => 1,
            'roles_mask' => 0,
            'registered' => time(),
        ]);

        UserExpanded::create([
            'id'      => $user->id,
            'name'    => $name,
            'surname' => $surname,
        ]);

        $selector    = Str::random(16);
        $plainToken  = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        UserConfirmation::create([
            'user_id'  => $user->id,
            'email'    => $email,
            'selector' => $selector,
            'token'    => $hashedToken,
            'expires'  => time() + 86400,
        ]);

        $keyFailed = false;
        try {
            $this->keyring->generateEd25519KeyPair($user->id);
        } catch (\Throwable $e) {
            $keyFailed = true;
        }

        $verifyUrl = url('/my/verify') . '?type=registration&selector=' . urlencode($selector) . '&token=' . urlencode($plainToken);
        Mail::to($email, "$name $surname")->locale(app()->getLocale())->send(new VerifyEmail($verifyUrl));

        return response()->json([
            'response' => 'success',
            'text' => [
                'header' => __('register-success-header'),
                'text'   => __('register-success-text', ['username' => $username, 'email' => $email])
                          . ($keyFailed ? ' ' . __('register-error-keys') : ''),
            ],
        ]);
    }
}
