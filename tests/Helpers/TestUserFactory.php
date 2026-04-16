<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Creates and caches test user accounts for the duration of a Pest run.
 *
 * Users are created exactly once per key (default / secondary) via the real
 * registration + email-verification flow so that all API tests operate against
 * a fully verified account without the overhead of Mailpit per test.
 */
class TestUserFactory
{
    /** @var array<string, array{email: string, username: string, password: string}> */
    private static array $users = [];

    /**
     * Return credentials for the named user, creating the account on first call.
     *
     * @return array{email: string, username: string, password: string}
     */
    public static function create(string $key = 'default'): array
    {
        if (!isset(self::$users[$key])) {
            self::$users[$key] = self::provision($key);
        }

        return self::$users[$key];
    }

    /** Drop cached state (useful in test teardown if a user was deleted). */
    public static function reset(string $key = 'default'): void
    {
        unset(self::$users[$key]);
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private static function provision(string $key): array
    {
        $mailpit = new MailpitClient();

        $ts       = time() . '-' . substr(md5($key . mt_rand()), 0, 6);
        $email    = "ls-test-{$ts}@example.com";
        $username = "lstest{$ts}";
        $password = 'TestPass_123!';

        $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
        $cookies = new CookieJar();
        $http    = new Client([
            'base_uri'        => $baseUrl,
            'http_errors'     => false,
            'allow_redirects' => false,
            'cookies'         => $cookies,
            'timeout'         => 20,
        ]);

        // ── 1. Get CSRF token ─────────────────────────────────────────────────
        $html = (string) $http->get('/auth/login')->getBody();
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $m);
        $csrfToken = $m[1] ?? '';

        // ── 2. Register ───────────────────────────────────────────────────────
        $http->post('/auth/register', [
            'form_params' => [
                'name'       => 'Test',
                'surname'    => 'User',
                'email'      => $email,
                'username'   => $username,
                'password'   => $password,
                'password-2' => $password,
            ],
            'headers' => ['X-CSRF-TOKEN' => $csrfToken],
        ]);

        // ── 3. Wait for the verification email ────────────────────────────────
        $mail = null;
        for ($i = 0; $i < 20; $i++) {
            $mail = $mailpit->getLatestTo($email);
            if ($mail !== null) {
                break;
            }
            sleep(1);
        }

        if ($mail === null) {
            throw new \RuntimeException(
                "TestUserFactory: verification email not delivered for {$email} (key={$key})"
            );
        }

        // ── 4. Click the verification link ────────────────────────────────────
        $verifyUrl = null;
        foreach ($mailpit->extractLinks($mail) as $link) {
            if (str_contains($link, '/my/verify') && str_contains($link, 'selector')) {
                $verifyUrl = $link;
                break;
            }
        }

        if ($verifyUrl === null) {
            throw new \RuntimeException(
                "TestUserFactory: no verification link found in email for {$email}"
            );
        }

        $parsed   = parse_url($verifyUrl);
        $relative = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        $http->get($relative);

        return compact('email', 'username', 'password');
    }
}
