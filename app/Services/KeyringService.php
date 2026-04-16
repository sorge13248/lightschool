<?php

namespace App\Services;

/**
 * Manages cryptographic key pairs for users.
 *
 * Two key pair formats are supported:
 *
 *  LEGACY (RSA-2048, phpseclib2):
 *    {secure_dir}/keyring/{userId}/public.key   — PEM
 *    {secure_dir}/keyring/{userId}/private.key  — PEM
 *
 *  CURRENT (Ed25519, libsodium):
 *    {secure_dir}/keyring/{userId}/public_ed25519.key   — base64(32 bytes)
 *    {secure_dir}/keyring/{userId}/private_ed25519.key  — base64(64 bytes)
 *
 * X25519 keys for sodium_crypto_box_seal are DERIVED at runtime from the Ed25519 keys
 * using sodium_crypto_sign_ed25519_{pk,sk}_to_curve25519(). Nothing extra is stored.
 */
class KeyringService
{
    protected string $secureDir;

    public function __construct()
    {
        $this->secureDir = config('lightschool.secure_dir', storage_path('secure'));
    }

    // ── Ed25519 (current) ─────────────────────────────────────────────────────

    /**
     * Generate and store an Ed25519 key pair for $userId.
     * Skips silently if the key pair already exists (use $force to overwrite).
     */
    public function generateEd25519KeyPair(int $userId, bool $force = false): void
    {
        $dir = $this->keyDir($userId);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pubFile  = $dir . '/public_ed25519.key';
        $privFile = $dir . '/private_ed25519.key';

        if (!$force && file_exists($pubFile) && file_exists($privFile)) {
            return;
        }

        $keypair = sodium_crypto_sign_keypair();
        $pk      = sodium_crypto_sign_publickey($keypair);   // 32 bytes
        $sk      = sodium_crypto_sign_secretkey($keypair);   // 64 bytes

        file_put_contents($pubFile,  base64_encode($pk));
        file_put_contents($privFile, base64_encode($sk));
        chmod($privFile, 0600);
    }

    /**
     * Returns the raw 32-byte Ed25519 public key for $userId.
     */
    public function getEd25519PublicKey(int $userId): string
    {
        return $this->readEd25519Key($userId, 'public');
    }

    /**
     * Returns the raw 64-byte Ed25519 secret key for $userId.
     */
    public function getEd25519SecretKey(int $userId): string
    {
        return $this->readEd25519Key($userId, 'private');
    }

    /**
     * Derives and returns the X25519 public key from the stored Ed25519 public key.
     * Used as the recipient key for sodium_crypto_box_seal.
     */
    public function getX25519PublicKey(int $userId): string
    {
        return sodium_crypto_sign_ed25519_pk_to_curve25519($this->getEd25519PublicKey($userId));
    }

    /**
     * Derives and returns the X25519 secret key from the stored Ed25519 secret key.
     * Used for sodium_crypto_box_seal_open.
     */
    public function getX25519SecretKey(int $userId): string
    {
        return sodium_crypto_sign_ed25519_sk_to_curve25519($this->getEd25519SecretKey($userId));
    }

    public function hasEd25519KeyPair(int $userId): bool
    {
        $dir = $this->keyDir($userId);
        return file_exists($dir . '/public_ed25519.key')
            && file_exists($dir . '/private_ed25519.key');
    }

    // ── Legacy RSA-2048 (phpseclib2) ──────────────────────────────────────────

    public function getPublicKey(int $userId): string
    {
        return $this->readKey($userId, 'public');
    }

    public function getPrivateKey(int $userId): string
    {
        return $this->readKey($userId, 'private');
    }

    public function hasKeyPair(int $userId): bool
    {
        return file_exists($this->keyDir($userId) . '/public.key')
            && file_exists($this->keyDir($userId) . '/private.key');
    }

    /**
     * Archive the legacy RSA key pair to keyring_archive/{userId}/.
     * Called by the crypto migration command after a user's data is fully migrated.
     */
    public function archiveLegacyKeyPair(int $userId): void
    {
        $srcDir  = $this->keyDir($userId);
        $destDir = $this->secureDir . DIRECTORY_SEPARATOR . 'keyring_archive'
                   . DIRECTORY_SEPARATOR . $userId;

        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        foreach (['public.key', 'private.key'] as $file) {
            $src = $srcDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($src)) {
                rename($src, $destDir . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    protected function keyDir(int $userId): string
    {
        return $this->secureDir . DIRECTORY_SEPARATOR . 'keyring' . DIRECTORY_SEPARATOR . $userId;
    }

    protected function readKey(int $userId, string $type): string
    {
        $file = $this->keyDir($userId) . "/{$type}.key";
        if (!file_exists($file)) {
            throw new \RuntimeException("RSA {$type} key not found for user {$userId}. Keyring may not have been initialised.");
        }

        $contents = file_get_contents($file);
        if ($contents === false || $contents === '') {
            throw new \RuntimeException("RSA {$type} key for user {$userId} could not be read or is empty.");
        }

        return $contents;
    }

    protected function readEd25519Key(int $userId, string $type): string
    {
        $suffix = $type === 'public' ? 'public_ed25519' : 'private_ed25519';
        $file   = $this->keyDir($userId) . "/{$suffix}.key";

        if (!file_exists($file)) {
            throw new \RuntimeException("Ed25519 {$type} key not found for user {$userId}. Run crypto:migrate to generate keys.");
        }

        $encoded = file_get_contents($file);
        if ($encoded === false || $encoded === '') {
            throw new \RuntimeException("Ed25519 {$type} key for user {$userId} could not be read or is empty.");
        }

        $raw = base64_decode(trim($encoded), true);
        if ($raw === false) {
            throw new \RuntimeException("Ed25519 {$type} key for user {$userId} is not valid base64.");
        }

        return $raw;
    }
}
