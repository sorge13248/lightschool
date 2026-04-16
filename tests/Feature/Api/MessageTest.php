<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| GET|POST /api/message
|--------------------------------------------------------------------------
*/

// ── Invalid type ──────────────────────────────────────────────────────────────

it('returns error for an invalid message type', function () {
    $creds = TestUserFactory::create('msg-default');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/message?type=nonexistent');
    expect($body['response'])->toBe('error');
});

// ── list ──────────────────────────────────────────────────────────────────────

it('returns conversation list for authenticated user', function () {
    $creds = TestUserFactory::create('msg-list');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->get('/api/message?type=list');
    expect($res->getStatusCode())->toBe(200);
});

// ── chat ──────────────────────────────────────────────────────────────────────

it('returns error for chat with a non-existent conversation id', function () {
    $creds = TestUserFactory::create('msg-chat');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiGet('/api/message?type=chat&id=999999');
    expect($body['response'])->toBe('error');
});

// ── new ───────────────────────────────────────────────────────────────────────

it('rejects new conversation when username is missing', function () {
    $creds = TestUserFactory::create('msg-new');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=new', [
        'body' => base64_encode('Hello'),
    ]);
    expect($body['response'])->toBe('error');
});

it('rejects new conversation when body is missing', function () {
    $creds = TestUserFactory::create('msg-new');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=new', [
        'username' => 'someone',
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when sending a message to a non-existent user', function () {
    $creds = TestUserFactory::create('msg-new2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=new', [
        'username' => 'nonexistent_' . time(),
        'body'     => base64_encode('Hello'),
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when trying to message yourself', function () {
    $creds = TestUserFactory::create('msg-self');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=new', [
        'username' => $creds['username'],
        'body'     => base64_encode('Hello'),
    ]);
    expect($body['response'])->toBe('error');
});

// ── send ──────────────────────────────────────────────────────────────────────

it('rejects send when conversation id is missing', function () {
    $creds = TestUserFactory::create('msg-send');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=send', [
        'body' => base64_encode('Hello'),
    ]);
    expect($body['response'])->toBe('error');
});

it('returns error when sending to a conversation the user is not part of', function () {
    $creds = TestUserFactory::create('msg-send2');
    $this->loginAs($creds['username'], $creds['password']);

    $body = $this->apiPost('/api/message?type=send&id=999999', [
        'body' => base64_encode('Hello'),
    ]);
    expect($body['response'])->toBe('error');
});
