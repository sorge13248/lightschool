<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/share
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid share type', function () {
    $creds = TestUserFactory::create('sh-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── get-all ───────────────────────────────────────────────────────────────────

it('returns all shares for authenticated user', function () {
    $creds = TestUserFactory::create('sh-getall');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=get-all');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('shares');
});

// ── get-shared ────────────────────────────────────────────────────────────────

it('returns files the user has shared', function () {
    $creds = TestUserFactory::create('sh-shared');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=get-shared');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('shares');
});

// ── get-sharing ───────────────────────────────────────────────────────────────

it('returns files shared with the user', function () {
    $creds = TestUserFactory::create('sh-sharing');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=get-sharing');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('shares');
});

// ── add ───────────────────────────────────────────────────────────────────────

it('returns error when sharing a non-existent file', function () {
    $creds  = TestUserFactory::create('sh-add');
    $target = TestUserFactory::create('sh-add-target');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/share?type=add', [
        'id' => 999999, 'username' => $target['username'],
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when sharing with a non-existent user', function () {
    $creds = TestUserFactory::create('sh-add2');
    $this->loginAs($creds['username'], $creds['password']);

    // Create a notebook to share
    $nb = $this->apiPost('/api/writer?type=create', ['name' => 'ShareTest ' . time()]);
    $id = $nb['id'];

    $body = $this->apiPost('/api/share?type=add', [
        'id' => $id, 'username' => 'nonexistent_user_' . time(),
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when sharing a file with yourself', function () {
    $creds = TestUserFactory::create('sh-self');
    $this->loginAs($creds['username'], $creds['password']);

    $nb = $this->apiPost('/api/writer?type=create', ['name' => 'SelfShare ' . time()]);
    $id = $nb['id'];

    $body = $this->apiPost('/api/share?type=add', [
        'id' => $id, 'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('error');
});

it('shares a file with another user and returns success', function () {
    $owner  = TestUserFactory::create('sh-owner');
    $target = TestUserFactory::create('sh-recipient');
    $this->loginAs($owner['username'], $owner['password']);

    $nb = $this->apiPost('/api/writer?type=create', ['name' => 'Shared NB ' . time()]);
    $id = $nb['id'];

    $body = $this->apiPost('/api/share?type=add', [
        'id' => $id, 'username' => $target['username'],
    ]);
    expect($body['response'])->toBe('success');
});

it('rejects sharing the same file with the same user twice', function () {
    $owner  = TestUserFactory::create('sh-dup-owner');
    $target = TestUserFactory::create('sh-dup-target');
    $this->loginAs($owner['username'], $owner['password']);

    $nb = $this->apiPost('/api/writer?type=create', ['name' => 'DupShare ' . time()]);
    $id = $nb['id'];

    $payload = ['id' => $id, 'username' => $target['username']];
    $this->apiPost('/api/share?type=add', $payload);
    $body = $this->apiPost('/api/share?type=add', $payload);
    expect($body['response'])->toBe('error');
});

// ── file-shared ───────────────────────────────────────────────────────────────

it('returns error for file-shared on a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('sh-fileshared');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=file-shared&id=999999');
    expect($body['response'])->toBe('error');
});

// ── delete ────────────────────────────────────────────────────────────────────

it('returns error when deleting a share that does not exist', function () {
    $creds = TestUserFactory::create('sh-delete');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/share?type=delete&id=999999&file_id=999999');
    expect($body['response'])->toBe('error');
});
