<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFaDeactivation;
use App\Models\User;
use App\Models\UserExpanded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct() {}

    /**
     * POST /auth/otp
     * Phase 1: user submits their username → send a 2FA-deactivation email.
     */
    public function requestDeactivation(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            ['username' => $request->input('username')],
            ['username' => 'required|string|max:128'],
            ['username.*' => __('username-required')]
        )) return $err;

        $username = trim((string) $request->input('username'));

        $user = User::where('username', $username)
            ->whereHas('expanded', fn($q) => $q->whereNotNull('twofa'))
            ->first(['id', 'email']);

        if (!$user) {
            return response()->json(['response' => 'error', 'text' => __('otp-user-not-found')]);
        }

        $token = Str::random(128);

        UserExpanded::where('id', $user->id)
            ->whereNotNull('twofa')
            ->update(['deac_twofa' => $token]);

        $deactivateUrl = url('/my/verify') . '?type=deactivate-twofa&token=' . urlencode($token);
        $locale = $user->expanded->language ?? config('app.locale', 'en');
        Mail::to($user->email)->locale($locale)->send(new TwoFaDeactivation($deactivateUrl));

        return response()->json(['response' => 'success', 'text' => __('deactivate-opt-ok')]);
    }
}
