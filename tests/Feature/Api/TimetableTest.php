<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/timetable
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid timetable type', function () {
    $creds = TestUserFactory::create('tt-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/timetable?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── get ───────────────────────────────────────────────────────────────────────

it('returns timetable rows for authenticated user', function () {
    $creds = TestUserFactory::create('tt-get');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->get('/api/timetable?type=get');
    expect($res->getStatusCode())->toBe(200);
});

// ── get-tomorrow ──────────────────────────────────────────────────────────────

it('returns tomorrow timetable rows', function () {
    $creds = TestUserFactory::create('tt-tomorrow');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->get('/api/timetable?type=get-tomorrow');
    expect($res->getStatusCode())->toBe(200);
});

// ── get-subjects ──────────────────────────────────────────────────────────────

it('returns distinct subjects', function () {
    $creds = TestUserFactory::create('tt-subjects');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->get('/api/timetable?type=get-subjects');
    expect($res->getStatusCode())->toBe(200);
});

// ── create ────────────────────────────────────────────────────────────────────

it('rejects create when day is missing', function () {
    $creds = TestUserFactory::create('tt-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=create', ['slot' => 1, 'subject' => 'Math']);
    expect($body['response'])->toBe('error');
});

it('rejects create when slot is missing', function () {
    $creds = TestUserFactory::create('tt-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=create', ['day' => 1, 'subject' => 'Math']);
    expect($body['response'])->toBe('error');
});

it('rejects create when subject is missing', function () {
    $creds = TestUserFactory::create('tt-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=create', ['day' => 1, 'slot' => 1]);
    expect($body['response'])->toBe('error');
});

it('rejects create when day is out of range', function () {
    $creds = TestUserFactory::create('tt-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=create', ['day' => 8, 'slot' => 1, 'subject' => 'Math']);
    expect($body['response'])->toBe('error');
});

it('creates a timetable entry', function () {
    $creds = TestUserFactory::create('tt-create');
    $this->loginAs($creds['username'], $creds['password']);

    $slot = rand(10, 250); // use a high slot to avoid conflicts across test runs
    $body = $this->apiPost('/api/timetable?type=create', [
        'day'     => 2,
        'slot'    => $slot,
        'subject' => 'Physics',
    ]);
    expect($body['response'])->toBe('success');
});

it('rejects duplicate slot creation', function () {
    $creds = TestUserFactory::create('tt-dupslot');
    $this->loginAs($creds['username'], $creds['password']);

    $slot = rand(10, 250);
    $this->apiPost('/api/timetable?type=create', ['day' => 3, 'slot' => $slot, 'subject' => 'Bio']);
    $body = $this->apiPost('/api/timetable?type=create', ['day' => 3, 'slot' => $slot, 'subject' => 'Chem']);
    expect($body['response'])->toBe('error');
});

// ── edit ──────────────────────────────────────────────────────────────────────

it('rejects edit when id is missing', function () {
    $creds = TestUserFactory::create('tt-edit');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=edit', ['day' => 1, 'slot' => 1, 'subject' => 'X']);
    expect($body['response'])->toBe('error');
});

it('returns error when editing a non-existent entry', function () {
    $creds = TestUserFactory::create('tt-edit');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=edit&id=999999', [
        'day' => 1, 'slot' => 1, 'subject' => 'X',
    ]);
    expect($body['response'])->toBe('error');
});

// ── remove ────────────────────────────────────────────────────────────────────

it('rejects remove when id is missing', function () {
    $creds = TestUserFactory::create('tt-remove');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/timetable?type=remove', []);
    expect($body['response'])->toBe('error');
});

it('returns error when removing a non-existent entry', function () {
    $creds = TestUserFactory::create('tt-remove');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/timetable?type=remove&id=999999');
    expect($body['response'])->toBe('error');
});

// ── create + edit + remove round-trip ─────────────────────────────────────────

it('creates, edits, then removes a timetable entry', function () {
    $creds = TestUserFactory::create('tt-roundtrip');
    $this->loginAs($creds['username'], $creds['password']);

    $slot = rand(10, 250);

    // Create
    $create = $this->apiPost('/api/timetable?type=create', [
        'day' => 4, 'slot' => $slot, 'subject' => 'History',
    ]);
    expect($create['response'])->toBe('success');

    // Get the id
    $rows = json_decode((string) $this->get('/api/timetable?type=get')->getBody(), true);
    $row  = collect($rows)->first(fn($r) => $r['slot'] === $slot && $r['day'] === 4);
    expect($row)->not->toBeNull();
    $id = $row['id'];

    // Edit
    $edit = $this->apiPost("/api/timetable?type=edit&id={$id}", [
        'day' => 4, 'slot' => $slot, 'subject' => 'History Edited',
    ]);
    expect($edit['response'])->toBe('success');

    // Remove
    $remove = $this->apiGet("/api/timetable?type=remove&id={$id}");
    expect($remove['response'])->toBe('success');
});
