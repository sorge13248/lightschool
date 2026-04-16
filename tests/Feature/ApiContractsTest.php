<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| API Response-Envelope Contracts
|--------------------------------------------------------------------------
|
| Every JSON endpoint must return {"response": "success"|"error", ...}.
| This suite verifies the envelope shape without caring about business logic.
|
*/

// ── POST /auth/register ───────────────────────────────────────────────────────

it('POST /auth/register returns JSON with a response key', function () {
    $res  = $this->postWithCsrf('/auth/register', []);
    $body = $this->json($res);

    expect($res->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($body)->toHaveKey('response')
        ->and($body['response'])->toBeIn(['success', 'error']);
});

// ── POST /auth/login ──────────────────────────────────────────────────────────

it('POST /auth/login returns JSON with a response key', function () {
    $res  = $this->postWithCsrf('/auth/login', ['username' => 'nobody', 'password' => 'irrelevant']);
    $body = $this->json($res);

    expect($res->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($body)->toHaveKey('response')
        ->and($body['response'])->toBeIn(['success', 'error', '2fa']);
});

it('POST /auth/login error response includes a non-empty text field', function () {
    $body = $this->apiPost('/auth/login', ['username' => 'nobody_xyz', 'password' => 'WrongPass999!']);

    expect($body['response'])->toBe('error')
        ->and($body)->toHaveKey('text')
        ->and($body['text'])->not->toBeEmpty();
});

// ── POST /auth/password/request ───────────────────────────────────────────────

it('POST /auth/password/request returns JSON with a response key', function () {
    $body = $this->apiPost('/auth/password/request', ['username' => 'nobody']);

    expect($body)->toHaveKey('response')
        ->and($body['response'])->toBeIn(['success', 'error']);
});

// ── POST /auth/otp ────────────────────────────────────────────────────────────

it('POST /auth/otp returns JSON with a response key', function () {
    $body = $this->apiPost('/auth/otp', ['username' => 'nobody']);

    expect($body)->toHaveKey('response')
        ->and($body['response'])->toBeIn(['success', 'error']);
});

// ── Authenticated API routes with invalid type return error envelope ──────────

it('GET /api/file-manager?type=invalid returns JSON error', function () {
    $creds = TestUserFactory::create();
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=invalid');

    expect($body['response'])->toBe('error');
});

it('GET /api/timetable?type=invalid returns JSON error', function () {
    $creds = TestUserFactory::create();
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/timetable?type=invalid');

    expect($body['response'])->toBe('error');
});
