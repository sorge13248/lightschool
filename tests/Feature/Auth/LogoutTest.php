<?php

use Tests\Helpers\TestUserFactory;

/*
|--------------------------------------------------------------------------
| POST /auth/logout
|--------------------------------------------------------------------------
*/

it('redirects to / after logout when logged in', function () {
    $creds = TestUserFactory::create('logout-test');
    $this->loginAs($creds['username'], $creds['password']);

    $res = $this->postWithCsrf('/auth/logout', []);
    expect($res->getStatusCode())->toBe(302);
    expect($res->getHeaderLine('Location'))->toContain('/');
});

it('invalidates the session so subsequent authenticated requests are refused', function () {
    $creds = TestUserFactory::create('logout-session');
    $this->loginAs($creds['username'], $creds['password']);

    // Confirm we're logged in — an API route should succeed (200 not 302)
    $before = $this->apiGet('/api/file-manager?type=list-files&folder=0');
    expect($before['response'])->toBeIn(['success', 'error']); // authenticated, not redirect

    // Logout
    $this->postWithCsrf('/auth/logout', []);
    $this->invalidateCsrfCache();

    // After logout, authenticated API routes reject guests (302 redirect or 401 depending on middleware)
    $res = $this->get('/api/file-manager?type=list-files&folder=0');
    expect($res->getStatusCode())->toBeIn([302, 401]);
});

it('unauthenticated logout still redirects (no crash)', function () {
    // Without logging in first, posting to logout should not 500
    $res = $this->postWithCsrf('/auth/logout', []);
    expect($res->getStatusCode())->toBeIn([302, 200]);
});
