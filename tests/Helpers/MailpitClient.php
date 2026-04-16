<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;

/**
 * Thin wrapper around the Mailpit REST API v1.
 * @see https://mailpit.axllent.org/docs/api-v1/
 */
class MailpitClient
{
    private Client $http;

    public function __construct()
    {
        $base = rtrim(getenv('MAILPIT_API') ?: 'http://localhost:8025', '/');
        $this->http = new Client(['base_uri' => $base, 'http_errors' => false, 'timeout' => 5]);
    }

    /** Return all messages currently in the inbox (newest first). */
    public function getMessages(): array
    {
        $response = $this->http->get('/api/v1/messages');
        return json_decode((string) $response->getBody(), true)['messages'] ?? [];
    }

    /**
     * Return the full message (including HTML body) for the most recent email
     * sent to $address, or null if none is found.
     */
    public function getLatestTo(string $address): ?array
    {
        foreach ($this->getMessages() as $msg) {
            foreach ($msg['To'] ?? [] as $recipient) {
                if (strtolower($recipient['Address']) === strtolower($address)) {
                    return $this->getMessage($msg['ID']);
                }
            }
        }
        return null;
    }

    /** Fetch a single message by ID (includes HTML/Text body). */
    public function getMessage(string $id): array
    {
        $response = $this->http->get("/api/v1/message/{$id}");
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Extract every href from the HTML body of a message.
     * Useful for pulling out verification/reset links.
     */
    public function extractLinks(array $message): array
    {
        $html = $message['HTML'] ?? '';
        preg_match_all('/href=["\']([^"\']+)["\']/', $html, $matches);
        return array_map('html_entity_decode', $matches[1] ?? []);
    }

    /** Delete all messages from the inbox. Call in beforeEach to isolate tests. */
    public function deleteAll(): void
    {
        $this->http->delete('/api/v1/messages');
    }
}
