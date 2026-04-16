<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/file-manager
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid type', function () {
    $creds = TestUserFactory::create('fm-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── list-files ────────────────────────────────────────────────────────────────

it('lists root files for authenticated user', function () {
    $creds = TestUserFactory::create('fm-list');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=list-files');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('items');
});

it('lists desktop favourites', function () {
    $creds = TestUserFactory::create('fm-desktop');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=list-files&folder=desktop');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('items');
});

it('returns error when listing files in a folder that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-folder-auth');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=list-files&folder=999999');
    expect($body['response'])->toBe('error');
});

// ── create-folder ─────────────────────────────────────────────────────────────

it('rejects folder creation when name is missing', function () {
    $creds = TestUserFactory::create('fm-createfolder');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=create-folder', []);
    expect($body['response'])->toBe('error');
});

it('creates a folder at the root', function () {
    $creds = TestUserFactory::create('fm-createfolder');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=create-folder', [
        'name' => 'Test Folder ' . time(),
    ]);
    expect($body['response'])->toBe('success');
});

it('rejects duplicate folder creation', function () {
    $creds = TestUserFactory::create('fm-dupfolder');
    $this->loginAs($creds['username'], $creds['password']);

    $name = 'DupFolder ' . time();
    $this->apiPost('/api/file-manager?type=create-folder', ['name' => $name]);
    $body = $this->apiPost('/api/file-manager?type=create-folder', ['name' => $name]);
    expect($body['response'])->toBe('error');
});

// ── details ───────────────────────────────────────────────────────────────────

it('returns error for details of a non-existent file id', function () {
    $creds = TestUserFactory::create('fm-details');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/file-manager?type=details&id=999999');
    expect($body['response'])->toBe('error');
});

// ── rename ────────────────────────────────────────────────────────────────────

it('rejects rename when name is missing', function () {
    $creds = TestUserFactory::create('fm-rename');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=rename&id=1', []);
    expect($body['response'])->toBe('error');
});

it('returns error when renaming a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-rename2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=rename&id=999999', ['name' => 'New Name']);
    expect($body['response'])->toBe('error');
});

// ── delete ────────────────────────────────────────────────────────────────────

it('returns error when deleting a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-delete');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=delete&id=999999', []);
    expect($body['response'])->toBe('error');
});

// ── fav ───────────────────────────────────────────────────────────────────────

it('returns error when favouriting a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-fav');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=fav&id=999999', []);
    expect($body['response'])->toBe('error');
});

// ── move ──────────────────────────────────────────────────────────────────────

it('returns error when moving a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-move');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=move&id=999999', ['folder' => '']);
    expect($body['response'])->toBe('error');
});

// ── list-trash ────────────────────────────────────────────────────────────────

it('lists the trash (may be empty)', function () {
    $creds = TestUserFactory::create('fm-trash');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->get('/api/file-manager?type=list-trash');
    expect($res->getStatusCode())->toBe(200);
});

// ── restore ───────────────────────────────────────────────────────────────────

it('returns error when restoring a file that does not belong to the user', function () {
    $creds = TestUserFactory::create('fm-restore');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=restore&id=999999', []);
    expect($body['response'])->toBe('error');
});

// ── empty ─────────────────────────────────────────────────────────────────────

it('empties the trash successfully', function () {
    $creds = TestUserFactory::create('fm-empty');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/file-manager?type=empty', []);
    expect($body['response'])->toBe('success');
});

// ── create + rename + fav + delete round-trip ─────────────────────────────────

it('creates, renames, favourites, then deletes a folder', function () {
    $creds = TestUserFactory::create('fm-roundtrip');
    $this->loginAs($creds['username'], $creds['password']);

    $ts   = time();
    $name = "RT Folder {$ts}";

    // Create
    $create = $this->apiPost('/api/file-manager?type=create-folder', ['name' => $name]);
    expect($create['response'])->toBe('success');

    // Find id
    $listBody = $this->apiGet('/api/file-manager?type=list-files');
    $item     = collect($listBody['items'])->first(fn($i) => $i['name'] === $name);
    expect($item)->not->toBeNull('Created folder should appear in list');
    $id = $item['id'];

    // Rename
    $rename = $this->apiPost("/api/file-manager?type=rename&id={$id}", ['name' => "RT Renamed {$ts}"]);
    expect($rename['response'])->toBe('success');

    // Fav
    $fav = $this->apiPost("/api/file-manager?type=fav&id={$id}", []);
    expect($fav['response'])->toBe('success');

    // Delete (move to trash)
    $del = $this->apiPost("/api/file-manager?type=delete&id={$id}", ['delete_mode' => 'move_to_trash']);
    expect($del['response'])->toBe('success');
});
