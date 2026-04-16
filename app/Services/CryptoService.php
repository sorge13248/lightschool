<?php

namespace App\Services;

/**
 * Unified encryption service.
 *
 * current scheme (libsodium secretstream + box_seal)
 */
class CryptoService
{
    public function __construct(
        protected SodiumCryptoService $sodium,
    ) {}

    // ── File / diary / notebook ───────────────────────────────────────────────

    /**
     * Encrypt $plaintext for $userId using the sodium scheme.
     *
     * @return array{data: string, key: string}
     */
    public function encrypt(string $plaintext, int $userId): array
    {
        $result = $this->sodium->encrypt($plaintext, $userId);

        return [
            'data'        => $result['data'],
            'key'         => $result['key'],
        ];
    }

    /**
     * Decrypt content.
     */
    public function decrypt(string $data, string $cypher, int $userId): string
    {
        return $this->sodium->decrypt($data, $cypher, $userId);
    }

    // ── Messages ──────────────────────────────────────────────────────────────

    /**
     * Encrypt a message body and optional attachment for $userId.
     *
     * @return array{body: string, attachment: string|null, key: string}
     */
    public function encryptMessage(string $body, ?string $attachment, int $userId): array
    {
        $result = $this->sodium->encryptMessage($body, $attachment, $userId);

        return [
            'body'        => $result['body'],
            'attachment'  => $result['attachment'],
            'key'         => $result['key'],
        ];
    }

    /**
     * Decrypt a message body and optional attachment.
     *
     * @return array{body: string, attachment: string|null}
     */
    public function decryptMessage(
        string $body,
        ?string $attachment,
        string $cypher,
        int $userId,
    ): array {
        return $this->sodium->decryptMessage($body, $attachment, $cypher, $userId);
    }

    // ── 2FA ───────────────────────────────────────────────────────────────────

    /**
     * Encrypt a TOTP secret string for $userId.
     * Returns base64 string (sodium).
     */
    public function encryptTwofa(string $totpSecret, int $userId): string
    {
        return $this->sodium->encryptTwofa($totpSecret, $userId);
    }

    /**
     * Decrypt a TOTP secret.
     *
     * $encrypted is base64 of the sealed box.
     */
    public function decryptTwofa(string $encrypted, int $userId): string
    {
        return $this->sodium->decryptTwofa($encrypted, $userId);
    }
}
