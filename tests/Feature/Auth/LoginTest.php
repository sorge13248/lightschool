<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| POST /auth/login
|--------------------------------------------------------------------------
*/

// ── Validation ────────────────────────────────────────────────────────────────

it('rejects login when username is missing', function () {
    $body = $this->apiPost('/auth/login', ['password' => 'SomePass1!']);
    expect($body['response'])->toBe('error');
});

it('rejects login when password is missing', function () {
    $body = $this->apiPost('/auth/login', ['username' => 'someone']);
    expect($body['response'])->toBe('error');
});

it('rejects login when both fields are empty', function () {
    $body = $this->apiPost('/auth/login', ['username' => '', 'password' => '']);
    expect($body['response'])->toBe('error');
});

// ── Wrong credentials ─────────────────────────────────────────────────────────

it('rejects login with a non-existent username', function () {
    $body = $this->apiPost('/auth/login', [
        'username' => 'no_such_user_' . time(),
        'password' => 'WrongPass999!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects login with a wrong password for a real user', function () {
    $creds = TestUserFactory::create();

    $body = $this->apiPost('/auth/login', [
        'username' => $creds['username'],
        'password' => 'definitely-wrong-password',
    ]);
    expect($body['response'])->toBe('error');
});

// ── Already logged in ─────────────────────────────────────────────────────────

it('returns 403 when already logged in', function () {
    $creds = TestUserFactory::create();
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->postWithCsrf('/auth/login', [
        'username' => $creds['username'],
        'password' => $creds['password'],
    ]);
    expect($res->getStatusCode())->toBe(403);
});

// ── Successful login ──────────────────────────────────────────────────────────

it('returns success with valid credentials', function () {
    $creds = TestUserFactory::create();
    $ok    = $this->loginAs($creds['username'], $creds['password']);

    expect($ok)->toBeTrue();
});

it('returns a success response body on valid login', function () {
    $creds = TestUserFactory::create();
    $body  = $this->apiPost('/auth/login', [
        'username' => $creds['username'],
        'password' => $creds['password'],
    ]);

    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('text');
});

// ── 2FA second-factor path ────────────────────────────────────────────────────

it('rejects the 2FA token step when no 2FA session is established', function () {
    $body = $this->apiPost('/auth/login?2fa=true', ['token' => '123456']);
    expect($body['response'])->toBe('error');
});
