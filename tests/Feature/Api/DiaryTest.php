<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/diary
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid diary type', function () {
    $creds = TestUserFactory::create('diary-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/diary?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── events ────────────────────────────────────────────────────────────────────

it('returns events for the current month', function () {
    $creds = TestUserFactory::create('diary-events');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/diary?type=events');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('events');
});

it('returns events for a specific month', function () {
    $creds = TestUserFactory::create('diary-events');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/diary?type=events&year=2024&month=6');
    expect($body['response'])->toBe('success');
});

// ── details ───────────────────────────────────────────────────────────────────

it('returns error for details of a non-existent diary entry', function () {
    $creds = TestUserFactory::create('diary-details');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/diary?type=details&id=999999');
    expect($body['response'])->toBe('error');
});

// ── create ────────────────────────────────────────────────────────────────────

it('rejects diary creation when type is missing', function () {
    $creds = TestUserFactory::create('diary-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=create', [
        'subject' => 'Test', 'date' => '2024-06-01',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects diary creation when subject is missing', function () {
    $creds = TestUserFactory::create('diary-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=create', [
        'type' => 'event', 'date' => '2024-06-01',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects diary creation when date is missing', function () {
    $creds = TestUserFactory::create('diary-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=create', [
        'type' => 'event', 'subject' => 'Test',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects diary creation with an invalid date', function () {
    $creds = TestUserFactory::create('diary-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=create', [
        'type' => 'event', 'subject' => 'Test', 'date' => 'not-a-date',
    ]);
    expect($body['response'])->toBe('error');
});

it('creates a diary entry', function () {
    $creds = TestUserFactory::create('diary-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=create', [
        'type'    => 'event',
        'subject' => 'Birthday ' . time(),
        'date'    => '2024-07-15',
        'color'   => '#ff0000',
    ]);
    expect($body['response'])->toBe('success');
});

// ── edit ──────────────────────────────────────────────────────────────────────

it('rejects edit when id is missing', function () {
    $creds = TestUserFactory::create('diary-edit');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=edit', [
        'subject' => 'X', 'date' => '2024-07-01',
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when editing a non-existent diary entry', function () {
    $creds = TestUserFactory::create('diary-edit');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/diary?type=edit', [
        'id' => 999999, 'subject' => 'X', 'date' => '2024-07-01',
    ]);
    expect($body['response'])->toBe('error');
});

// ── create + details + edit round-trip ───────────────────────────────────────

it('creates, reads details, then edits a diary entry', function () {
    $creds = TestUserFactory::create('diary-roundtrip');
    $this->loginAs($creds['username'], $creds['password']);

    $ts = time();

    // Create
    $create = $this->apiPost('/api/diary?type=create', [
        'type'    => 'event',
        'subject' => "RT Event {$ts}",
        'date'    => '2024-08-10',
    ]);
    expect($create['response'])->toBe('success');

    // Find id via events
    $events = $this->apiGet('/api/diary?type=events&year=2024&month=8');
    $event  = collect($events['events'])->first(fn($e) => $e['name'] === "RT Event {$ts}");
    expect($event)->not->toBeNull();
    $id = $event['id'];

    // Details
    $details = $this->apiGet("/api/diary?type=details&id={$id}");
    expect($details['response'])->toBe('success')
        ->and($details['event']['name'])->toBe("RT Event {$ts}");

    // Edit
    $edit = $this->apiPost("/api/diary?type=edit", [
        'id'      => $id,
        'subject' => "RT Event Edited {$ts}",
        'date'    => '2024-08-10',
    ]);
    expect($edit['response'])->toBe('success');
});
