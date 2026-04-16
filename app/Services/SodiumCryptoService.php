<?php

namespace App\Services;

/**
 * Encryption service using libsodium (PHP 7.2+, no extra Composer dependency).
 *
 * Scheme:
 *   1. Generate a random 32-byte symmetric key.
 *   2. Encrypt plaintext with XChaCha20-Poly1305 via sodium_crypto_secretstream_xchacha20poly1305.
 *      Output: [24-byte header][ciphertext+tag]  → base64-encoded → stored in `html` column.
 *   3. Seal the symmetric key for the recipient with sodium_crypto_box_seal (X25519 key derived
 *      from the user's stored Ed25519 public key) → base64-encoded → stored in `cypher` column.
 *
 * For 2FA secrets (short strings):
 *   sodium_crypto_box_seal($totpSecret, $x25519_pk) → base64 → stored in `twofa`.
 *
 * Key derivation:
 *   Ed25519 keys are stored on disk. X25519 keys are derived at runtime via
 *   sodium_crypto_sign_ed25519_{pk,sk}_to_curve25519().
 */
class SodiumCryptoService
{
    public function __construct(protected KeyringService $keyring) {}

    // ── File / diary / notebook content ──────────────────────────────────────

    /**
     * Encrypt $plaintext for $userId.
     *
     * @return array{data: string, key: string}  Both values are base64 strings.
     */
    public function encrypt(string $plaintext, int $userId): array
    {
        $symmetricKey = random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);

        [$header, $ciphertext] = $this->secretstreamEncrypt($plaintext, $symmetricKey);

        $sealedKey = sodium_crypto_box_seal(
            $symmetricKey,
            $this->keyring->getX25519PublicKey($userId)
        );

        sodium_memzero($symmetricKey);

        return [
            'data' => base64_encode($header . $ciphertext),
            'key'  => base64_encode($sealedKey),
        ];
    }

    /**
     * Decrypt $data (base64) using the sealed key $cypher (base64) for $userId.
     */
    public function decrypt(string $data, string $cypher, int $userId): string
    {
        $symmetricKey = $this->unsealKey($cypher, $userId);

        $raw    = base64_decode($data, true);
        if ($raw === false) {
            throw new \RuntimeException("sodium decrypt: data is not valid base64 for user {$userId}.");
        }

        $headerLen  = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $header     = substr($raw, 0, $headerLen);
        $ciphertext = substr($raw, $headerLen);

        $plaintext = $this->secretstreamDecrypt($header, $ciphertext, $symmetricKey);

        sodium_memzero($symmetricKey);

        return $plaintext;
    }

    // ── Message body + attachment (shared secretstream key) ───────────────────

    /**
     * Encrypt a message body and optional attachment under a single sealed key.
     *
     * @return array{body: string, attachment: string|null, key: string}
     */
    public function encryptMessage(string $body, ?string $attachment, int $userId): array
    {
        $symmetricKey = random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);

        [$bodyHeader, $bodyCiphertext] = $this->secretstreamEncrypt($body, $symmetricKey);

        $encAttachment = null;
        if ($attachment !== null) {
            [$attHeader, $attBody] = $this->secretstreamEncrypt($attachment, $symmetricKey);
            $encAttachment = base64_encode($attHeader . $attBody);
        }

        $sealedKey = sodium_crypto_box_seal(
            $symmetricKey,
            $this->keyring->getX25519PublicKey($userId)
        );

        sodium_memzero($symmetricKey);

        return [
            'body'       => base64_encode($bodyHeader . $bodyCiphertext),
            'attachment' => $encAttachment,
            'key'        => base64_encode($sealedKey),
        ];
    }

    /**
     * Decrypt a message body and optional attachment.
     *
     * @return array{body: string, attachment: string|null}
     */
    public function decryptMessage(string $body, ?string $attachment, string $cypher, int $userId): array
    {
        $symmetricKey = $this->unsealKey($cypher, $userId);

        $decBody       = $this->decryptBlob($body, $symmetricKey, $userId, 'body');
        $decAttachment = null;

        if ($attachment !== null) {
            $decAttachment = $this->decryptBlob($attachment, $symmetricKey, $userId, 'attachment');
        }

        sodium_memzero($symmetricKey);

        return ['body' => $decBody, 'attachment' => $decAttachment];
    }

    // ── 2FA secret ────────────────────────────────────────────────────────────

    /**
     * Encrypt a TOTP secret string for $userId using sodium_crypto_box_seal.
     * Returns base64-encoded sealed box.
     */
    public function encryptTwofa(string $totpSecret, int $userId): string
    {
        $sealed = sodium_crypto_box_seal(
            $totpSecret,
            $this->keyring->getX25519PublicKey($userId)
        );

        return base64_encode($sealed);
    }

    /**
     * Decrypt a TOTP secret that was sealed with encryptTwofa.
     */
    public function decryptTwofa(string $encryptedBase64, int $userId): string
    {
        $sealed = base64_decode($encryptedBase64, true);
        if ($sealed === false) {
            throw new \RuntimeException("sodium decryptTwofa: not valid base64 for user {$userId}.");
        }

        $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->keyring->getX25519SecretKey($userId),
            $this->keyring->getX25519PublicKey($userId)
        );

        $plaintext = sodium_crypto_box_seal_open($sealed, $keypair);
        if ($plaintext === false) {
            throw new \RuntimeException("sodium decryptTwofa: decryption failed for user {$userId}. Key mismatch or corrupt data.");
        }

        return $plaintext;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /**
     * @return array{string, string}  [header, ciphertext]
     */
    private function secretstreamEncrypt(string $plaintext, string $key): array
    {
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        $ciphertext = sodium_crypto_secretstream_xchacha20poly1305_push(
            $state,
            $plaintext,
            '',
            SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
        );

        return [$header, $ciphertext];
    }

    private function secretstreamDecrypt(string $header, string $ciphertext, string $key): string
    {
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);

        [$plaintext, $tag] = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $ciphertext);

        if ($plaintext === false) {
            throw new \RuntimeException('sodium secretstream decryption failed: authentication tag mismatch.');
        }

        return $plaintext;
    }

    private function unsealKey(string $cypherBase64, int $userId): string
    {
        $sealed = base64_decode($cypherBase64, true);
        if ($sealed === false) {
            throw new \RuntimeException("sodium decrypt: cypher is not valid base64 for user {$userId}.");
        }

        $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->keyring->getX25519SecretKey($userId),
            $this->keyring->getX25519PublicKey($userId)
        );

        $symmetricKey = sodium_crypto_box_seal_open($sealed, $keypair);
        if ($symmetricKey === false) {
            throw new \RuntimeException("sodium decrypt: key unsealing failed for user {$userId}. Key mismatch or corrupt cypher.");
        }

        return $symmetricKey;
    }

    private function decryptBlob(string $base64, string $key, int $userId, string $label): string
    {
        $raw = base64_decode($base64, true);
        if ($raw === false) {
            throw new \RuntimeException("sodium decrypt: {$label} is not valid base64 for user {$userId}.");
        }

        $headerLen  = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $header     = substr($raw, 0, $headerLen);
        $ciphertext = substr($raw, $headerLen);

        return $this->secretstreamDecrypt($header, $ciphertext, $key);
    }
}
