<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Exceptions\InvalidPasskey;
use Spatie\LaravelPasskeys\Models\Passkey;
use Spatie\LaravelPasskeys\Support\Config;

class PasskeyController extends Controller
{
    /** GET /api/passkeys — list passkeys for the authenticated user */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $passkeys = $user->passkeys()
            ->orderBy('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn (Passkey $p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'last_used_at' => $p->last_used_at?->toISOString(),
                'created_at'   => $p->created_at->toISOString(),
            ]);

        return response()->json(['response' => 'success', 'passkeys' => $passkeys]);
    }

    /** GET /api/passkeys/register-options — generate WebAuthn registration challenge */
    public function registerOptions(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var GeneratePasskeyRegisterOptionsAction $action */
        $action = Config::getAction('generate_passkey_register_options', GeneratePasskeyRegisterOptionsAction::class);

        $optionsJson = $action->execute($user, asJson: true);

        // Store for verification on the next request
        Session::put('passkey-registration-options', $optionsJson);

        return response()->json(json_decode($optionsJson, true));
    }

    /** POST /api/passkeys/register — store a new passkey */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'passkey'         => ['required', 'json'],
            'passkey_options' => ['required', 'json'],
            'name'            => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        $passkeyOptionsJson = Session::pull('passkey-registration-options');

        if (! $passkeyOptionsJson) {
            return response()->json(['response' => 'error', 'text' => __('passkey-session-expired')]);
        }

        /** @var StorePasskeyAction $action */
        $action = Config::getAction('store_passkey', StorePasskeyAction::class);

        try {
            $action->execute(
                authenticatable: $user,
                passkeyJson: $request->input('passkey'),
                passkeyOptionsJson: $passkeyOptionsJson,
                hostName: parse_url(config('app.url'), PHP_URL_HOST),
                additionalProperties: ['name' => trim($request->input('name'))],
            );
        } catch (InvalidPasskey $e) {
            Log::warning('Passkey registration failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'cause'   => $e->getPrevious()?->getMessage(),
            ]);
            return response()->json(['response' => 'error', 'text' => __('passkey-register-failed')]);
        }

        return response()->json(['response' => 'success', 'text' => __('passkey-register-success')]);
    }

    /** DELETE /api/passkeys/{id} — remove a passkey */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $deleted = $user->passkeys()->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['response' => 'error', 'text' => __('passkey-not-found')]);
        }

        return response()->json(['response' => 'success', 'text' => __('passkey-deleted')]);
    }
}
