<?php

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
|
| All public GET routes must be reachable without authentication and must
| return HTTP 200 with an HTML body.
|
*/

it('serves the homepage', function () {
    expect($this->get('/')->getStatusCode())->toBe(200);
});

it('serves the overview page', function () {
    expect($this->get('/overview')->getStatusCode())->toBe(200);
});

it('serves the features page', function () {
    expect($this->get('/features')->getStatusCode())->toBe(200);
});

it('serves the privacy page', function () {
    expect($this->get('/privacy')->getStatusCode())->toBe(200);
});

it('serves the cookie page', function () {
    expect($this->get('/cookie')->getStatusCode())->toBe(200);
});

it('serves the language page', function () {
    expect($this->get('/language')->getStatusCode())->toBe(200);
});

it('returns 404 for an unknown path', function () {
    expect($this->get('/this-route-does-not-exist-xyz')->getStatusCode())->toBe(404);
});

it('returns HTML content-type for public pages', function () {
    expect($this->get('/')->getHeaderLine('Content-Type'))->toContain('text/html');
});

it('serves the login page', function () {
    expect($this->get('/auth/login')->getStatusCode())->toBe(200);
});

it('serves the register page', function () {
    expect($this->get('/auth/register')->getStatusCode())->toBe(200);
});

it('serves the password-reset page', function () {
    expect($this->get('/auth/password')->getStatusCode())->toBe(200);
});

it('serves the OTP deactivation page', function () {
    expect($this->get('/auth/otp')->getStatusCode())->toBe(200);
});

it('serves translations as JavaScript for a valid locale', function () {
    $res = $this->get('/lang/en.js');

    expect($res->getStatusCode())->toBe(200)
        ->and($res->getHeaderLine('Content-Type'))->toContain('javascript')
        ->and((string) $res->getBody())->toStartWith('var LANGUAGE =');
});

it('falls back to the default locale for an unknown locale', function () {
    $res = $this->get('/lang/xx.js');

    expect($res->getStatusCode())->toBe(200)
        ->and((string) $res->getBody())->toStartWith('var LANGUAGE =');
});

it('returns a cacheable JS translation file', function () {
    $res = $this->get('/lang/en.js');

    expect($res->getHeaderLine('Cache-Control'))->toContain('max-age=86400');
});
