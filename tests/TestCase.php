<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected Client $http;
    protected CookieJar $cookies;
    protected string $baseUrl;

    /** Cached plain CSRF token extracted from the last fetched page. */
    private ?string $csrfTokenCache = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
        $this->cookies = new CookieJar();
        $this->http    = new Client([
            'base_uri'        => $this->baseUrl,
            'http_errors'     => false,
            'allow_redirects' => false,
            'cookies'         => $this->cookies,
            'timeout'         => 15,
        ]);
    }

    // ── Basic HTTP helpers ────────────────────────────────────────────────────

    protected function get(string $path, array $options = []): ResponseInterface
    {
        return $this->http->get($path, $options);
    }

    protected function post(string $path, array $formData, array $options = []): ResponseInterface
    {
        return $this->http->post($path, array_merge(['form_params' => $formData], $options));
    }

    /** Decode a JSON response body to an associative array. */
    protected function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    // ── CSRF helpers ──────────────────────────────────────────────────────────

    /**
     * Return the plain CSRF token by extracting it from the csrf-token meta tag.
     * Fetches /auth/login once and caches the result for the lifetime of the test.
     */
    protected function csrfToken(): string
    {
        if ($this->csrfTokenCache === null) {
            $html = (string) $this->get('/auth/login')->getBody();
            preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $m);
            $this->csrfTokenCache = $m[1] ?? '';
        }
        return $this->csrfTokenCache;
    }

    /** Drop the cached CSRF token (call after logout or session invalidation). */
    protected function invalidateCsrfCache(): void
    {
        $this->csrfTokenCache = null;
    }

    /**
     * POST with automatic X-CSRF-TOKEN header.
     * This is the standard way to call any state-changing endpoint in tests.
     */
    protected function postWithCsrf(string $path, array $formData, array $options = []): ResponseInterface
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'X-CSRF-TOKEN' => $this->csrfToken(),
        ]);
        return $this->post($path, $formData, $options);
    }

    // ── Auth helpers ──────────────────────────────────────────────────────────

    /**
     * Log in with the given credentials using the current session.
     * Returns true on success, false otherwise.
     */
    protected function loginAs(string $username, string $password): bool
    {
        $res  = $this->postWithCsrf('/auth/login', compact('username', 'password'));
        $body = $this->json($res);
        $ok   = ($body['response'] ?? '') === 'success';
        if ($ok) {
            $this->invalidateCsrfCache();
        }
        return $ok;
    }

    /**
     * Log out, clearing the session and cached CSRF token.
     */
    protected function logout(): void
    {
        $this->postWithCsrf('/auth/logout', []);
        $this->invalidateCsrfCache();
    }

    // ── Secondary client (for two-user tests) ─────────────────────────────────

    /**
     * Create a separate HTTP client already logged in as the given user.
     * Used in tests that need two simultaneous authenticated sessions.
     */
    protected function clientFor(string $username, string $password): Client
    {
        $cookies = new CookieJar();
        $http    = new Client([
            'base_uri'        => $this->baseUrl,
            'http_errors'     => false,
            'allow_redirects' => false,
            'cookies'         => $cookies,
            'timeout'         => 15,
        ]);

        $html = (string) $http->get('/auth/login')->getBody();
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $m);
        $token = $m[1] ?? '';

        $http->post('/auth/login', [
            'form_params' => compact('username', 'password'),
            'headers'     => ['X-CSRF-TOKEN' => $token],
        ]);

        return $http;
    }

    // ── JSON API shortcuts ────────────────────────────────────────────────────

    /**
     * GET an API path and return the decoded JSON body.
     */
    protected function apiGet(string $path): array
    {
        return $this->json($this->get($path));
    }

    /**
     * POST with CSRF and return the decoded JSON body.
     */
    protected function apiPost(string $path, array $formData): array
    {
        return $this->json($this->postWithCsrf($path, $formData));
    }
}
