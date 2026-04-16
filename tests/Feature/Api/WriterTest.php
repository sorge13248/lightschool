<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/writer
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid writer type', function () {
    $creds = TestUserFactory::create('wr-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/writer?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── get ───────────────────────────────────────────────────────────────────────

it('returns error when getting a non-existent notebook', function () {
    $creds = TestUserFactory::create('wr-get');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/writer?type=get&id=999999');
    expect($body['response'])->toBe('error');
});

it('returns error when accessing another user\'s notebook without share', function () {
    $creds = TestUserFactory::create('wr-access');
    $this->loginAs($creds['username'], $creds['password']);

    // Create a notebook as this user, then try to access it as another
    $create = $this->apiPost('/api/writer?type=create', ['name' => 'Private NB ' . time()]);
    expect($create['response'])->toBe('success');
    $notebookId = $create['id'];

    // Login as a different user and try to access it
    $this->logout();
    $other = TestUserFactory::create('wr-access-other');
    $this->loginAs($other['username'], $other['password']);

    $body = $this->apiGet("/api/writer?type=get&id={$notebookId}");
    expect($body['response'])->toBe('error');
});

// ── create ────────────────────────────────────────────────────────────────────

it('rejects notebook creation when name is missing', function () {
    $creds = TestUserFactory::create('wr-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/writer?type=create', []);
    expect($body['response'])->toBe('error');
});

it('creates a notebook', function () {
    $creds = TestUserFactory::create('wr-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/writer?type=create', [
        'name'    => 'NB ' . time(),
        'content' => '<p>Hello world</p>',
    ]);
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('id');
});

it('rejects duplicate notebook name in same folder', function () {
    $creds = TestUserFactory::create('wr-dupname');
    $this->loginAs($creds['username'], $creds['password']);

    $name = 'DupNB ' . time();
    $this->apiPost('/api/writer?type=create', ['name' => $name]);
    $body = $this->apiPost('/api/writer?type=create', ['name' => $name]);
    expect($body['response'])->toBe('error');
});

// ── edit ──────────────────────────────────────────────────────────────────────

it('rejects edit when id is missing', function () {
    $creds = TestUserFactory::create('wr-edit');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/writer?type=edit', ['content' => '<p>x</p>']);
    expect($body['response'])->toBe('error');
});

it('returns error when editing a notebook that does not belong to the user', function () {
    $creds = TestUserFactory::create('wr-edit2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/writer?type=edit&id=999999', ['content' => '<p>x</p>']);
    expect($body['response'])->toBe('error');
});

// ── create + get + edit round-trip ────────────────────────────────────────────

it('creates, reads, then edits a notebook', function () {
    $creds = TestUserFactory::create('wr-roundtrip');
    $this->loginAs($creds['username'], $creds['password']);

    $ts = time();

    // Create
    $create = $this->apiPost('/api/writer?type=create', [
        'name'    => "RT NB {$ts}",
        'content' => '<p>Initial</p>',
    ]);
    expect($create['response'])->toBe('success');
    $id = $create['id'];

    // Get
    $get = $this->apiGet("/api/writer?type=get&id={$id}");
    expect($get['response'])->toBe('success')
        ->and($get['name'])->toBe("RT NB {$ts}");

    // Edit
    $edit = $this->apiPost("/api/writer?type=edit&id={$id}", [
        'name'    => "RT NB Edited {$ts}",
        'content' => '<p>Updated</p>',
    ]);
    expect($edit['response'])->toBe('success');
});
