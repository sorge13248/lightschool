<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserConfirmation;
use App\Models\UserExpanded;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VerifyController extends Controller
{
    /** GET /my/verify?type=registration&selector=X&token=Y */
    public function verify(Request $request): InertiaResponse
    {
        $type     = $request->query('type');
        $selector = $request->query('selector');
        $token    = $request->query('token');

        if ($type === 'registration') {
            return $this->verifyEmail($selector, $token);
        }

        if ($type === 'deactivate-twofa') {
            return $this->deactivateTwoFa($token);
        }

        return $this->render(false, __('verify-unknown-type'));
    }

    protected function verifyEmail(?string $selector, ?string $token): InertiaResponse
    {
        if (!$selector || !$token) {
            return $this->render(false, __('verify-invalid-link'));
        }

        $record = UserConfirmation::where('selector', $selector)->first();

        if (!$record) {
            return $this->render(false, __('verify-link-not-found'));
        }

        if ($record->expires < time()) {
            $record->delete();
            return $this->render(false, __('verify-link-expired'));
        }

        if (!hash_equals($record->token, hash('sha256', $token))) {
            return $this->render(false, __('verify-invalid-token'));
        }

        User::where('id', $record->user_id)->update(['verified' => 1]);

        // Update email if it changed (email re-verification flow)
        $user = User::find($record->user_id);
        if ($user && $user->email !== $record->email) {
            $user->update(['email' => $record->email]);
        }

        $record->delete();

        return $this->render(true, __('verify-email-success'));
    }

    protected function deactivateTwoFa(?string $token): InertiaResponse
    {
        if (!$token) {
            return $this->render(false, __('verify-invalid-deac-link'));
        }

        $updated = UserExpanded::where('deac_twofa', $token)
            ->whereNotNull('twofa')
            ->update(['twofa' => null, 'deac_twofa' => null]);

        if ($updated) {
            return $this->render(true, __('verify-twofa-disabled'));
        }

        return $this->render(false, __('verify-twofa-invalid'));
    }

    private function render(bool $success, string $message): InertiaResponse
    {
        return Inertia::render('auth/Verify', compact('success', 'message'));
    }
}
