<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/contact
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid contact type', function () {
    $creds = TestUserFactory::create('ct-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/contact?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── get-contacts ──────────────────────────────────────────────────────────────

it('returns contacts list for authenticated user', function () {
    $creds = TestUserFactory::create('ct-list');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/contact?type=get-contacts');
    expect($body['response'])->toBe('success')
        ->and($body)->toHaveKey('contacts');
});

// ── details ───────────────────────────────────────────────────────────────────

it('returns error for details of a non-existent contact', function () {
    $creds = TestUserFactory::create('ct-details');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/contact?type=details&id=999999');
    expect($body['response'])->toBe('error');
});

// ── create ────────────────────────────────────────────────────────────────────

it('rejects contact creation when name is missing', function () {
    $creds = TestUserFactory::create('ct-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=create', [
        'surname' => 'Doe', 'username' => 'someuser',
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects contact creation when username is missing', function () {
    $creds = TestUserFactory::create('ct-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=create', [
        'name' => 'John', 'surname' => 'Doe',
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when adding a contact with a non-existent username', function () {
    $creds = TestUserFactory::create('ct-create');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=create', [
        'name' => 'John', 'surname' => 'Doe', 'username' => 'definitely_nonexistent_' . time(),
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when trying to add yourself as a contact', function () {
    $creds = TestUserFactory::create('ct-self');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=create', [
        'name' => 'Me', 'surname' => 'Self', 'username' => $creds['username'],
    ]);
    expect($body['response'])->toBe('error');
});

it('adds another registered user as a contact', function () {
    $creds  = TestUserFactory::create('ct-adder');
    $target = TestUserFactory::create('ct-target');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=create', [
        'name'     => 'Target',
        'surname'  => 'User',
        'username' => $target['username'],
    ]);
    expect($body['response'])->toBe('success');
});

it('rejects adding the same contact twice', function () {
    $creds  = TestUserFactory::create('ct-dup-adder');
    $target = TestUserFactory::create('ct-dup-target');
    $this->loginAs($creds['username'], $creds['password']);

    $payload = ['name' => 'Dup', 'surname' => 'User', 'username' => $target['username']];
    $this->apiPost('/api/contact?type=create', $payload);
    $body = $this->apiPost('/api/contact?type=create', $payload);
    expect($body['response'])->toBe('error');
});

// ── fav ───────────────────────────────────────────────────────────────────────

it('favourites an existing contact', function () {
    $creds  = TestUserFactory::create('ct-fav-user');
    $target = TestUserFactory::create('ct-fav-target');
    $this->loginAs($creds['username'], $creds['password']);

    // Add contact first
    $this->apiPost('/api/contact?type=create', [
        'name' => 'F', 'surname' => 'T', 'username' => $target['username'],
    ]);

    // Get id
    $list    = $this->apiGet('/api/contact?type=get-contacts');
    $contact = collect($list['contacts'])->first(
        fn($c) => ($c['username'] ?? '') === $target['username']
    );
    expect($contact)->not->toBeNull();
    $id = $contact['id'];

    // Fav
    $body = $this->apiGet("/api/contact?type=fav&id={$id}&action=add");
    expect($body['response'])->toBe('success');
});

// ── block ─────────────────────────────────────────────────────────────────────

it('returns error when blocking a non-existent username', function () {
    $creds = TestUserFactory::create('ct-block');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/contact?type=block', [
        'username' => 'totally_nonexistent_' . time(),
    ]);
    expect($body['response'])->toBe('error');
});

it('blocks and then unblocks a user', function () {
    $creds  = TestUserFactory::create('ct-blockuser');
    $target = TestUserFactory::create('ct-blocktarget');
    $this->loginAs($creds['username'], $creds['password']);

    $block = $this->apiPost('/api/contact?type=block', ['username' => $target['username']]);
    expect($block['response'])->toBe('success')
        ->and(strtolower($block['text']))->toContain('blocked');

    $unblock = $this->apiPost('/api/contact?type=block', ['username' => $target['username']]);
    expect($unblock['response'])->toBe('success')
        ->and(strtolower($unblock['text']))->toContain('unblocked');
});

// ── delete ────────────────────────────────────────────────────────────────────

it('trashes a contact (type=trash)', function () {
    $creds  = TestUserFactory::create('ct-trash-user');
    $target = TestUserFactory::create('ct-trash-target');
    $this->loginAs($creds['username'], $creds['password']);

    $this->apiPost('/api/contact?type=create', [
        'name' => 'T', 'surname' => 'D', 'username' => $target['username'],
    ]);

    $list    = $this->apiGet('/api/contact?type=get-contacts');
    $contact = collect($list['contacts'])->first(
        fn($c) => ($c['username'] ?? '') === $target['username']
    );
    $id = $contact['id'];

    $body = $this->apiPost("/api/contact?type=delete&id={$id}", ['type' => 'trash']);
    expect($body['response'])->toBe('success');
});
