<?php

declare(strict_types=1);

namespace Gate\Security;

/**
 * Encryption at rest (libsodium secretbox, XSalsa20-Poly1305) and keyed hashing, derived from the app key.
 * Ciphertext format: "v1:" . base64(nonce . box).
 */
final class Crypto
{
    private readonly string $encryptionKey;
    private readonly string $hmacKey;

    public function __construct(string $appKeyBase64)
    {
        $raw = base64_decode($appKeyBase64, true);
        if ($raw === false || strlen($raw) !== 32) {
            throw new \InvalidArgumentException('app.key must be base64 of exactly 32 random bytes.');
        }
        $this->encryptionKey = sodium_crypto_generichash('gate-lebanon:encryption', $raw, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $this->hmacKey = sodium_crypto_generichash('gate-lebanon:hmac', $raw, 32);
    }

    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public function encrypt(#[\SensitiveParameter] string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $this->encryptionKey));
    }

    public function decrypt(string $ciphertext): string
    {
        if (!str_starts_with($ciphertext, 'v1:')) {
            throw new \RuntimeException('Unsupported ciphertext format.');
        }
        $raw = base64_decode(substr($ciphertext, 3), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            throw new \RuntimeException('Malformed ciphertext.');
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->encryptionKey);
        if ($plain === false) {
            throw new \RuntimeException('Ciphertext could not be decrypted (wrong key or tampered data).');
        }
        return $plain;
    }

    /** Hex HMAC-SHA256 with a key derived from the app key. */
    public function hmac(#[\SensitiveParameter] string $data): string
    {
        return hash_hmac('sha256', $data, $this->hmacKey);
    }
}
