<?php

use Tests\Helpers\MailpitClient;
use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| POST /auth/register
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    (new MailpitClient())->deleteAll();
});

// ── Required-field validation ─────────────────────────────────────────────────

it('rejects when all fields are missing', function () {
    $body = $this->apiPost('/auth/register', []);
    expect($body['response'])->toBe('error');
});

it('rejects when name is missing', function () {
    $body = $this->apiPost('/auth/register', [
        'surname' => 'User', 'email' => 'x@example.com',
        'username' => 'xuser', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects when surname is missing', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'email' => 'x@example.com',
        'username' => 'xuser', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects when email is missing', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User',
        'username' => 'xuser', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects when username is missing', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User',
        'email' => 'x@example.com', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

// ── Field-level validation ────────────────────────────────────────────────────

it('rejects an invalid email address', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'not-an-email',
        'username' => 'validuser', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects a username shorter than 4 characters', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'ab@example.com',
        'username' => 'ab', 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects a password shorter than 8 characters', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'short@example.com',
        'username' => 'shortpw', 'password' => 'abc', 'password-2' => 'abc',
    ]);
    expect($body['response'])->toBe('error')
        ->and($body['text'])->toContain('8');
});

it('rejects a common password', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'common@example.com',
        'username' => 'commonpw', 'password' => 'password', 'password-2' => 'password',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects mismatched passwords', function () {
    $body = $this->apiPost('/auth/register', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'mismatch@example.com',
        'username' => 'mismatch', 'password' => 'Pass1234!', 'password-2' => 'Other1234!',
    ]);
    expect($body['response'])->toBe('error');
});

// ── Duplicate detection ───────────────────────────────────────────────────────

it('rejects a duplicate username', function () {
    $ts   = time();
    $user = "dupuser{$ts}";

    $this->apiPost('/auth/register', [
        'name' => 'Dup', 'surname' => 'One', 'email' => "dup1-{$ts}@example.com",
        'username' => $user, 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);

    $body = $this->apiPost('/auth/register', [
        'name' => 'Dup', 'surname' => 'Two', 'email' => "dup2-{$ts}@example.com",
        'username' => $user, 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);

    expect($body['response'])->toBe('error')
        ->and(strtolower($body['text']))->toContain('username');
});

it('rejects a duplicate email address', function () {
    $ts    = time();
    $email = "dupemail{$ts}@example.com";

    $this->apiPost('/auth/register', [
        'name' => 'Dup', 'surname' => 'One', 'email' => $email,
        'username' => "dupemail1{$ts}", 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);

    $body = $this->apiPost('/auth/register', [
        'name' => 'Dup', 'surname' => 'Two', 'email' => $email,
        'username' => "dupemail2{$ts}", 'password' => 'Pass1234!', 'password-2' => 'Pass1234!',
    ]);

    expect($body['response'])->toBe('error')
        ->and(strtolower($body['text']))->toContain('email');
});

// ── Successful registration ───────────────────────────────────────────────────

it('registers a new user and sends a verification email', function () {
    $mailpit  = new MailpitClient();
    $ts       = time();
    $email    = "newuser{$ts}@example.com";
    $username = "newuser{$ts}";

    $body = $this->apiPost('/auth/register', [
        'name'       => 'New',
        'surname'    => 'User',
        'email'      => $email,
        'username'   => $username,
        'password'   => 'NewPass_123!',
        'password-2' => 'NewPass_123!',
    ]);

    expect($body['response'])->toBe('success');

    $mail = null;
    for ($i = 0; $i < 15; $i++) {
        $mail = $mailpit->getLatestTo($email);
        if ($mail) break;
        sleep(1);
    }
    expect($mail)->not->toBeNull('Verification email was not delivered');

    $links = $mailpit->extractLinks($mail);
    $verifyLink = collect($links)->first(
        fn($l) => str_contains($l, '/my/verify') && str_contains($l, 'selector')
    );
    expect($verifyLink)->not->toBeNull('Verification link missing from email');
});

// ── Already logged in ─────────────────────────────────────────────────────────

it('returns 403 when trying to register while already logged in', function () {
    $creds = TestUserFactory::create('register-403');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->postWithCsrf('/auth/register', [
        'name' => 'X', 'surname' => 'X', 'email' => 'x@example.com',
        'username' => 'xuser', 'password' => 'Xpass1234!', 'password-2' => 'Xpass1234!',
    ]);
    expect($res->getStatusCode())->toBe(403);
});
