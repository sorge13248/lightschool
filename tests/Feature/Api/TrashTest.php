<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/trash
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid trash type', function () {
    $creds = TestUserFactory::create('trash-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/trash?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── get ───────────────────────────────────────────────────────────────────────

it('returns trash items list (may be empty)', function () {
    $creds = TestUserFactory::create('trash-get');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/trash?type=get');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('items');
});

// ── delete ────────────────────────────────────────────────────────────────────

it('returns error when permanently deleting a non-existent or not-in-trash item', function () {
    $creds = TestUserFactory::create('trash-delete');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/trash?type=delete&id=999999');
    expect($body['response'])->toBe('error');
});

// ── restore ───────────────────────────────────────────────────────────────────

it('returns error when restoring a non-existent or not-in-trash item', function () {
    $creds = TestUserFactory::create('trash-restore');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/trash?type=restore&id=999999');
    expect($body['response'])->toBe('error');
});

// ── empty ─────────────────────────────────────────────────────────────────────

it('empties the trash', function () {
    $creds = TestUserFactory::create('trash-empty');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/trash?type=empty');
    expect($body['response'])->toBe('success');
});

// ── move-to-trash + restore + delete round-trip ───────────────────────────────

it('moves a folder to trash via file-manager, then restores it from trash', function () {
    $creds = TestUserFactory::create('trash-roundtrip');
    $this->loginAs($creds['username'], $creds['password']);

    $name = 'TrashTest Folder ' . time();

    // Create a folder
    $create = $this->apiPost('/api/file-manager?type=create-folder', ['name' => $name]);
    expect($create['response'])->toBe('success');

    // Get its id
    $list   = $this->apiGet('/api/file-manager?type=list-files');
    $item   = collect($list['items'])->first(fn($i) => $i['name'] === $name);
    expect($item)->not->toBeNull();
    $id = $item['id'];

    // Move to trash via file-manager
    $del = $this->apiPost("/api/file-manager?type=delete&id={$id}", ['delete_mode' => 'move_to_trash']);
    expect($del['response'])->toBe('success');

    // Should now appear in trash
    $trashList = $this->apiGet('/api/trash?type=get');
    $inTrash   = collect($trashList['items'])->first(fn($i) => $i['id'] === $id);
    expect($inTrash)->not->toBeNull('Item should appear in trash');

    // Restore it
    $restore = $this->apiGet("/api/trash?type=restore&id={$id}");
    expect($restore['response'])->toBe('success');

    // Should no longer appear in trash
    $trashAfter = $this->apiGet('/api/trash?type=get');
    $stillInTrash = collect($trashAfter['items'])->first(fn($i) => $i['id'] === $id);
    expect($stillInTrash)->toBeNull('Item should no longer be in trash after restore');
});
