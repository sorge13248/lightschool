<?php

/*
|--------------------------------------------------------------------------
| Parameterized URL Routing
|--------------------------------------------------------------------------
|
| Verifies that the Laravel router resolves all parameterized routes.
| A 404 here means a route constraint or definition is broken.
|
*/

// ── Writer ────────────────────────────────────────────────────────────────────

it('resolves /my/app/writer (no folder)', function () {
    expect($this->get('/my/app/writer')->getStatusCode())->not->toBe(404);
});

it('resolves /my/app/writer/{folder}', function () {
    expect($this->get('/my/app/writer/1')->getStatusCode())->not->toBe(404);
});

// ── Reader ────────────────────────────────────────────────────────────────────

it('resolves /my/app/reader/notebook/{id}', function () {
    expect($this->get('/my/app/reader/notebook/1')->getStatusCode())->not->toBe(404);
});

it('resolves /my/app/reader/file/{id}', function () {
    expect($this->get('/my/app/reader/file/1')->getStatusCode())->not->toBe(404);
});

it('resolves /my/app/reader/diary/{id}', function () {
    expect($this->get('/my/app/reader/diary/1')->getStatusCode())->not->toBe(404);
});

it('returns 404 for /my/app/reader with an invalid type', function () {
    expect($this->get('/my/app/reader/pdf/1')->getStatusCode())->toBe(404);
});

// ── Icon serving ──────────────────────────────────────────────────────────────

it('resolves /img/icon/app/{name}', function () {
    expect($this->get('/img/icon/app/desktop')->getStatusCode())->not->toBe(404);
});

it('resolves /img/icon/common/{name}', function () {
    expect($this->get('/img/icon/common/folder')->getStatusCode())->not->toBe(404);
});

it('returns 404 for /img/icon with an invalid type', function () {
    expect($this->get('/img/icon/invalid/file')->getStatusCode())->toBe(404);
});

// ── File serve route ──────────────────────────────────────────────────────────

it('recognises /api/file/{id} — unauthenticated returns 403 not 404', function () {
    expect($this->get('/api/file/1')->getStatusCode())->toBe(403);
});

it('returns 404 for /api/file/{non-numeric}', function () {
    expect($this->get('/api/file/abc')->getStatusCode())->toBe(404);
});

// ── Dashboard redirect ────────────────────────────────────────────────────────

it('redirects /my/app/desktop to the file-manager with folder=desktop', function () {
    $res = $this->get('/my/app/desktop');
    expect($res->getStatusCode())->toBe(302)
        ->and($res->getHeaderLine('Location'))->toContain('folder=desktop');
});
