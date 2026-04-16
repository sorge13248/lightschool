<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Legacy encryption service — Defuse AES-256 + phpseclib2 RSA-2048-OAEP-SHA-512.
 *
 * READ-ONLY in production: used only to decrypt enc_version=1 rows during the
 * crypto migration and for dual-read while migration is in progress.
 * All new writes go through SodiumCryptoService.
 * 
 * @deprecated
 */
class LegacyCryptoService
{
    public function __construct(protected KeyringService $keyring) {}

    /**
     * Decrypt $data using the RSA-wrapped Defuse key stored in $cypher.
     *
     * $cypher is the raw binary RSA ciphertext (as stored in the DB binary column
     * before the schema migration widened it to TEXT). After the schema migration
     * the column is TEXT but the legacy rows still hold raw binary — pass them
     * through as-is; phpseclib handles the binary string directly.
     */
    public function decrypt(string $data, string $cypher, int $userId): string
    {
        $privatePem = $this->keyring->getPrivateKey($userId);

        $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($privatePem)
            ->withHash('sha512');

        if (!$rsa instanceof \phpseclib3\Crypt\RSA\PrivateKey) {
            throw new \RuntimeException("Expected an RSA private key for user {$userId}");
        }

        try {
            $keyAscii = $rsa->decrypt($cypher);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Legacy RSA decryption failed for user {$userId}: " . $e->getMessage(), 0, $e);
        }

        if ($keyAscii === '') {
            throw new \RuntimeException("Legacy RSA decryption produced no output for user {$userId}. Key may be corrupt or mismatched.");
        }

        $defuseKey = Key::loadFromAsciiSafeString($keyAscii);

        return Crypto::decrypt($data, $defuseKey);
    }

    /**
     * Decrypt a 2FA TOTP secret that was RSA-encrypted with the user's public key.
     * $encrypted is the raw binary ciphertext stored in users_expanded.twofa.
     */
    public function decryptTwofa(string $encrypted, int $userId): string
    {
        $privatePem = $this->keyring->getPrivateKey($userId);

        $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($privatePem)
            ->withHash('sha512');

        if (!$rsa instanceof \phpseclib3\Crypt\RSA\PrivateKey) {
            throw new \RuntimeException("Expected an RSA private key for user {$userId}");
        }

        $secret = $rsa->decrypt($encrypted);

        if ($secret === '') {
            throw new \RuntimeException("Legacy RSA 2FA decryption produced no output for user {$userId}.");
        }

        return $secret;
    }

    /**
     * Encrypt $plaintext for $userId using the legacy scheme.
     * Only used by the migration command to re-read; kept here for completeness.
     *
     * @return array{data: string, key: string}
     */
    public function encrypt(string $plaintext, int $userId): array
    {
        $defuseKey  = Key::createNewRandomKey();
        $ciphertext = Crypto::encrypt($plaintext, $defuseKey);
        $keyAscii   = $defuseKey->saveToAsciiSafeString();

        $publicPem = $this->keyring->getPublicKey($userId);

        $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($publicPem)
            ->withHash('sha512');

        if (!$rsa instanceof \phpseclib3\Crypt\RSA\PublicKey) {
            throw new \RuntimeException("Expected an RSA public key for user {$userId}");
        }

        $encryptedKey = $rsa->encrypt($keyAscii);

        if ($encryptedKey === '') {
            throw new \RuntimeException("Legacy RSA encryption produced no output for user {$userId}.");
        }

        return [
            'data' => $ciphertext,
            'key'  => $encryptedKey,
        ];
    }
}
