<?php

declare(strict_types=1);

namespace BMMatic\Security;

/** Argon2id password hashing and the password policy. */
final class PasswordHasher
{
    public const MIN_LENGTH = 12;
    public const MAX_LENGTH = 256;

    /** Argon2id cost: 64 MB, 4 iterations, 1 thread (the libsodium implementation on shared hosts supports 1 thread). */
    private const OPTIONS = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];

    private const COMMON = [
        'password1234', 'password12345', '123456789012', 'qwertyuiop12', '1234567890ab', 'iloveyou1234', 'welcome12345',
        'administrator', 'admin1234567', 'letmein12345', 'passw0rd1234', 'bmmatic12345', 'bm-matic1234', 'aalst1234567',
        'changeme1234', 'qwerty123456', 'azertyuiop12', 'wachtwoord12', 'motdepasse12', 'trustno11234',
    ];

    private static ?string $dummyHash = null;

    public function hash(#[\SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, self::OPTIONS);
    }

    public function verify(#[\SensitiveParameter] string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::OPTIONS);
    }

    /** Spends the same time as a real verification (used when the account does not exist). */
    public function verifyDummy(#[\SensitiveParameter] string $password): void
    {
        self::$dummyHash ??= $this->hash(bin2hex(random_bytes(16)));
        password_verify($password, self::$dummyHash);
    }

    public static function isSupported(): bool
    {
        return defined('PASSWORD_ARGON2ID');
    }

    /**
     * Returns translation keys of the policy rules the password breaks (empty when it is acceptable).
     *
     * @return list<string>
     */
    public function policyErrors(#[\SensitiveParameter] string $password, string $email = ''): array
    {
        $errors = [];
        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH) {
            $errors[] = 'validation.password_min';
        }
        if ($length > self::MAX_LENGTH) {
            $errors[] = 'validation.password_max';
        }
        $lower = mb_strtolower($password);
        if (in_array($lower, self::COMMON, true) || count(array_unique(mb_str_split($password))) < 5) {
            $errors[] = 'validation.password_common';
        }
        $local = mb_strtolower((string) strstr($email, '@', true));
        if ($local !== '' && mb_strlen($local) >= 4 && str_contains($lower, $local)) {
            $errors[] = 'validation.password_contains_email';
        }
        return $errors;
    }
}
