<?php

use Tests\Helpers\MailpitClient;
use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| POST /auth/password/request  and  POST /auth/password/reset
|--------------------------------------------------------------------------
*/

// ── POST /auth/password/request ───────────────────────────────────────────────

it('rejects password-reset request when username is missing', function () {
    $body = $this->apiPost('/auth/password/request', []);
    expect($body['response'])->toBe('error');
});

it('returns success for a non-existent username (enumeration protection)', function () {
    $body = $this->apiPost('/auth/password/request', [
        'username' => 'totally_nonexistent_' . time(),
    ]);
    expect($body['response'])->toBe('success');
});

it('returns success and sends a reset email for an existing user', function () {
    $mailpit = new MailpitClient();
    $mailpit->deleteAll();

    $creds = TestUserFactory::create('pw-request');

    $body = $this->apiPost('/auth/password/request', [
        'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('success');

    $mail = null;
    for ($i = 0; $i < 15; $i++) {
        $mail = $mailpit->getLatestTo($creds['email']);
        if ($mail) break;
        sleep(1);
    }
    expect($mail)->not->toBeNull('Password-reset email was not delivered');

    $links    = $mailpit->extractLinks($mail);
    $resetUrl = collect($links)->first(
        fn($l) => str_contains($l, '/my/password') && str_contains($l, 'selector')
    );
    expect($resetUrl)->not->toBeNull('Reset link missing from email');
});

// ── POST /auth/password/reset ─────────────────────────────────────────────────

it('rejects reset when selector is missing', function () {
    $body = $this->apiPost('/auth/password/reset', [
        'token'      => 'sometoken',
        'password'   => 'NewPass1234!',
        'password-2' => 'NewPass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects reset when token is missing', function () {
    $body = $this->apiPost('/auth/password/reset', [
        'selector'   => 'someselector',
        'password'   => 'NewPass1234!',
        'password-2' => 'NewPass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects reset when password is too short', function () {
    $body = $this->apiPost('/auth/password/reset', [
        'selector'   => 'someselector',
        'token'      => 'sometoken',
        'password'   => 'abc',
        'password-2' => 'abc',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects reset when passwords do not match', function () {
    $body = $this->apiPost('/auth/password/reset', [
        'selector'   => 'someselector',
        'token'      => 'sometoken',
        'password'   => 'NewPass1234!',
        'password-2' => 'DifferentPass!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects reset with an invalid or expired selector', function () {
    $body = $this->apiPost('/auth/password/reset', [
        'selector'   => 'invalid_selector_that_does_not_exist',
        'token'      => 'sometoken',
        'password'   => 'NewPass1234!',
        'password-2' => 'NewPass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('completes a full password-reset cycle via email link', function () {
    $mailpit = new MailpitClient();
    $mailpit->deleteAll();

    $creds = TestUserFactory::create('pw-reset-cycle');

    // 1 — Request reset
    $this->apiPost('/auth/password/request', ['username' => $creds['username']]);

    // 2 — Wait for email
    $mail = null;
    for ($i = 0; $i < 15; $i++) {
        $mail = $mailpit->getLatestTo($creds['email']);
        if ($mail) break;
        sleep(1);
    }
    expect($mail)->not->toBeNull('Reset email not delivered');

    // 3 — Extract selector + token from URL
    $links    = $mailpit->extractLinks($mail);
    $resetUrl = collect($links)->first(
        fn($l) => str_contains($l, '/my/password') && str_contains($l, 'selector')
    );
    expect($resetUrl)->not->toBeNull('Reset URL missing from email');

    parse_str(parse_url($resetUrl, PHP_URL_QUERY), $qs);
    expect($qs)->toHaveKeys(['selector', 'token']);

    // 4 — Submit new password
    $newPassword = 'ResetNew_9988!';
    $body        = $this->apiPost('/auth/password/reset', [
        'selector'   => $qs['selector'],
        'token'      => $qs['token'],
        'password'   => $newPassword,
        'password-2' => $newPassword,
    ]);
    expect($body['response'])->toBe('success');

    // 5 — Login with new password succeeds
    TestUserFactory::reset('pw-reset-cycle');
    $ok = $this->loginAs($creds['username'], $newPassword);
    expect($ok)->toBeTrue('Login with new password should succeed after reset');
});
