<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/project
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid project type', function () {
    $creds = TestUserFactory::create('proj-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/project?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── delete ────────────────────────────────────────────────────────────────────

it('returns error when deleting project without a project_code cookie', function () {
    $creds = TestUserFactory::create('proj-delete');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/project?type=delete', []);
    expect($body['response'])->toBe('error');
});

// ── code ──────────────────────────────────────────────────────────────────────

it('generates and returns a project code', function () {
    $creds = TestUserFactory::create('proj-code');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/project?type=code');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('code');
});

// ── your-files ────────────────────────────────────────────────────────────────

it('returns the user\'s notebooks for project sharing', function () {
    $creds = TestUserFactory::create('proj-yourfiles');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/project?type=your-files');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('files');
});

// ── files-by-code ─────────────────────────────────────────────────────────────

it('returns error when looking up files for an invalid project code', function () {
    $creds = TestUserFactory::create('proj-bycode');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/project?type=files-by-code&code=INVALID_CODE_999');
    expect($body['response'])->toBe('error');
});

// ── stop ──────────────────────────────────────────────────────────────────────

it('returns error when stopping a project without a project_code cookie', function () {
    $creds = TestUserFactory::create('proj-stop');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/project?type=stop', []);
    expect($body['response'])->toBe('error');
});
