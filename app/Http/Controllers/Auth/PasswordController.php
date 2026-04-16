<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordReset as PasswordResetMail;
use App\Models\User;
use App\Models\UserExpanded;
use App\Models\UserReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function __construct() {}

    /** POST /auth/password/request — request a password reset */
    public function requestReset(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            ['username' => $request->input('username')],
            ['username' => 'required|string|max:128'],
            ['username.*' => __('username-required')]
        )) return $err;

        $username = trim((string) $request->input('username'));
        $user     = User::where('username', $username)->first();

        // Always return success to avoid username enumeration
        if (!$user) {
            return response()->json(['response' => 'success', 'text' => ['header' => __('password-reset-request-header'), 'text' => __('password-reset-request-text')]]);
        }

        $selector   = Str::random(20);
        $plainToken = Str::random(64);

        UserReset::create([
            'user'     => $user->id,
            'selector' => $selector,
            'token'    => hash('sha256', $plainToken),
            'expires'  => time() + 3600,
        ]);

        $resetUrl = url('/my/password') . '?selector=' . urlencode($selector) . '&token=' . urlencode($plainToken);
        $locale = $user->expanded->language ?? config('app.locale', 'en');
        Mail::to($user->email)->locale($locale)->send(new PasswordResetMail($resetUrl));

        return response()->json(['response' => 'success', 'text' => ['header' => __('password-reset-request-header'), 'text' => __('password-reset-request-text')]]);
    }

    /** POST /auth/password/reset — set a new password using selector + token */
    public function reset(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            $request->only(['selector', 'token', 'password', 'password-2']),
            [
                'selector'   => 'required|string',
                'token'      => 'required|string',
                'password'   => 'required|string|min:8',
                'password-2' => 'required|same:password',
            ],
            [
                'selector.required'  => __('password-reset-invalid'),
                'token.required'     => __('password-reset-invalid'),
                'password.min'       => __('password-reset-too-short'),
                'password-2.same'    => __('password-reset-no-match'),
            ]
        )) return $err;

        $selector = $request->input('selector');
        $token    = $request->input('token');
        $password = $request->input('password');

        $record = UserReset::where('selector', $selector)->first();

        if (!$record || $record->expires < time()) {
            return response()->json(['response' => 'error', 'text' => __('password-reset-expired')]);
        }
        if (!hash_equals($record->token, hash('sha256', $token))) {
            return response()->json(['response' => 'error', 'text' => __('password-reset-invalid-token')]);
        }

        User::where('id', $record->user)->update(['password' => Hash::make($password)]);
        UserExpanded::where('id', $record->user)->update(['password_last_change' => now()]);
        $record->delete();

        return response()->json(['response' => 'success', 'text' => __('password-reset-success')]);
    }
}
