<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/settings
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid settings type', function () {
    $creds = TestUserFactory::create('set-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/settings?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── account ───────────────────────────────────────────────────────────────────

it('rejects account update when name is missing', function () {
    $creds = TestUserFactory::create('set-account');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=account', [
        'surname' => 'User', 'email' => $creds['email'], 'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects account update when email is invalid', function () {
    $creds = TestUserFactory::create('set-account');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=account', [
        'name' => 'Test', 'surname' => 'User', 'email' => 'not-an-email', 'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('error');
});

it('updates account details successfully', function () {
    $creds = TestUserFactory::create('set-account-upd');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=account', [
        'name'     => 'Updated',
        'surname'  => 'Name',
        'email'    => $creds['email'],
        'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('success');
});

it('rejects account update when username is already taken', function () {
    $creds1 = TestUserFactory::create('set-acct-usr1');
    $creds2 = TestUserFactory::create('set-acct-usr2');
    $this->loginAs($creds1['username'], $creds1['password']);

    $body = $this->apiPost('/api/settings?type=account', [
        'name'     => 'Test',
        'surname'  => 'User',
        'email'    => $creds1['email'],
        'username' => $creds2['username'],
    ]);
    expect($body['response'])->toBe('error');
});

// ── customize ─────────────────────────────────────────────────────────────────

it('saves customize settings', function () {
    $creds = TestUserFactory::create('set-customize');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=customize', [
        'accent' => 'ff0000',
    ]);
    expect($body['response'])->toBe('success');
});

// ── privacy ───────────────────────────────────────────────────────────────────

it('saves privacy settings', function () {
    $creds = TestUserFactory::create('set-privacy');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=privacy', [
        'search_visible'  => 1,
        'show_email'      => 0,
        'show_username'   => 1,
        'send_messages'   => 2,
        'share_documents' => 1,
        'ms_office'       => 0,
    ]);
    expect($body['response'])->toBe('success');
});

// ── password ──────────────────────────────────────────────────────────────────

it('rejects password change when old password field is missing', function () {
    $creds = TestUserFactory::create('set-pw');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=password', [
        'new' => 'NewPass1234!', 'new-2' => 'NewPass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects password change when new passwords do not match', function () {
    $creds = TestUserFactory::create('set-pw');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=password', [
        'old'   => $creds['password'],
        'new'   => 'NewPass1234!',
        'new-2' => 'DifferentPass!',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects password change when old password is wrong', function () {
    $creds = TestUserFactory::create('set-pw-wrong');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=password', [
        'old'   => 'WrongOldPass999!',
        'new'   => 'NewPass1234!',
        'new-2' => 'NewPass1234!',
    ]);
    expect($body['response'])->toBe('error');
});

it('changes password successfully with correct old password', function () {
    $creds = TestUserFactory::create('set-pw-ok');
    $this->loginAs($creds['username'], $creds['password']);

    $newPass = 'Changed_Pass_9!' . time();
    $body    = $this->apiPost('/api/settings?type=password', [
        'old'   => $creds['password'],
        'new'   => $newPass,
        'new-2' => $newPass,
    ]);
    expect($body['response'])->toBe('success');

    // Mark user as stale so TestUserFactory re-creates it next time
    TestUserFactory::reset('set-pw-ok');
});

// ── app-to-taskbar ────────────────────────────────────────────────────────────

it('returns error when app name is missing', function () {
    $creds = TestUserFactory::create('set-taskbar');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=app-to-taskbar', []);
    expect($body['response'])->toBe('error');
});

it('returns error for a non-existent app name', function () {
    $creds = TestUserFactory::create('set-taskbar2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/settings?type=app-to-taskbar&app=nonexistent_app_' . time());
    expect($body['response'])->toBe('error');
});

// ── erase-app-data ────────────────────────────────────────────────────────────

it('returns error when app name is missing for erase-app-data', function () {
    $creds = TestUserFactory::create('set-erase');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=erase-app-data', []);
    expect($body['response'])->toBe('error');
});

it('returns error for an invalid app name in erase-app-data', function () {
    $creds = TestUserFactory::create('set-erase2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/settings?type=erase-app-data&app=nonexistent_app');
    expect($body['response'])->toBe('error');
});

// ── twofa-activate ────────────────────────────────────────────────────────────

it('returns error for 2FA activation when session secret is not set', function () {
    $creds = TestUserFactory::create('set-2fa');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=twofa-activate', [
        'password' => $creds['password'],
        'token'    => '123456',
    ]);
    expect($body['response'])->toBe('error');
});

// ── twofa-deactivate ──────────────────────────────────────────────────────────

it('rejects 2FA deactivation when password is missing', function () {
    $creds = TestUserFactory::create('set-2fa-deac');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=twofa-deactivate', []);
    expect($body['response'])->toBe('error');
});

it('rejects 2FA deactivation with wrong password', function () {
    $creds = TestUserFactory::create('set-2fa-deac2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=twofa-deactivate', [
        'password' => 'WrongPassword999!',
    ]);
    expect($body['response'])->toBe('error');
});

// ── regenerate-keys ───────────────────────────────────────────────────────────

it('regenerates RSA keys successfully', function () {
    $creds = TestUserFactory::create('set-regen');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/settings?type=regenerate-keys', []);
    expect($body['response'])->toBe('success');
});
