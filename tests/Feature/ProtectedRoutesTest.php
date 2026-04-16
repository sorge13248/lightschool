<?php

/*
|--------------------------------------------------------------------------
| Protected App Page Routes
|--------------------------------------------------------------------------
|
| All /my/app/* HTML shells are publicly renderable (the auth check happens
| on the API side). This suite verifies that visiting these routes without a
| session never returns a 5xx and always returns 200.
|
| The actual data API routes (/api/*) require auth and redirect unauthenticated
| requests to /my (302). Those are verified separately.
|
*/

$pageRoutes = [
    'File manager'       => '/my/app/file-manager',
    'Desktop redirect'   => '/my/app/desktop',
    'Contact'            => '/my/app/contact',
    'Diary'              => '/my/app/diary',
    'Message'            => '/my/app/message',
    'Settings'           => '/my/app/settings',
    'Settings account'   => '/my/app/settings/account',
    'Settings app'       => '/my/app/settings/app',
    'Settings customize' => '/my/app/settings/customize',
    'Settings security'  => '/my/app/settings/security',
    'Settings password'  => '/my/app/settings/password',
    'Settings 2fa'       => '/my/app/settings/2fa',
    'Share'              => '/my/app/share',
    'Timetable'          => '/my/app/timetable',
    'Trash'              => '/my/app/trash',
    'Writer'             => '/my/app/writer',
    'Project'            => '/my/app/project',
];

foreach ($pageRoutes as $label => $path) {
    it("renders {$label} without a 5xx error for guests", function () use ($path) {
        $status = $this->get($path)->getStatusCode();
        expect($status)->not->toBeIn([500, 502, 503]);
    });
}

// ── Unauthenticated API access must redirect, not 5xx ────────────────────────

$apiRoutes = [
    '/api/trash',
    '/api/share',
    '/api/contact',
    '/api/diary',
    '/api/message',
    '/api/settings',
    '/api/timetable',
    '/api/writer',
    '/api/project',
];

foreach ($apiRoutes as $path) {
    it("redirects unauthenticated GET to {$path}", function () use ($path) {
        $status = $this->get($path)->getStatusCode();
        expect($status)->toBe(302);
    });
}

it('returns 403 for unauthenticated GET /api/file/{id}', function () {
    expect($this->get('/api/file/1')->getStatusCode())->toBe(403);
});
