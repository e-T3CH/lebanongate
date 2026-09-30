<?php

declare(strict_types=1);

namespace BMMatic\Install;

use BMMatic\Core\Paths;

/**
 * Installer lock and claim.
 *  - storage/installed.lock: written when installation finished; the installer is then gone (404).
 *  - storage/install.claim: the first browser session that submits a step owns the installer for 60 minutes, so a
 *    second visitor cannot take over a half-finished installation.
 */
final class InstallState
{
    public const CLAIM_TTL = 3600;

    public static function lockFile(): string
    {
        return Paths::storage('installed.lock');
    }

    public static function isLocked(): bool
    {
        return is_file(self::lockFile());
    }

    public static function lock(string $version): void
    {
        $content = json_encode(['installed_at' => gmdate('c'), 'version' => $version], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if (file_put_contents(self::lockFile(), $content, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write ' . self::lockFile());
        }
        @unlink(Paths::storage('install.claim'));
    }

    /**
     * True when this session owns (or has just claimed) the installer. Called when a step is submitted: looking at
     * the installer claims nothing, so a crawler that finds a freshly uploaded site cannot lock the owner out.
     */
    public static function claim(string $sessionId, int $now): bool
    {
        if (self::claimedByOther($sessionId, $now)) {
            return false;
        }
        return file_put_contents(Paths::storage('install.claim'), json_encode(['session' => hash('sha256', $sessionId), 'at' => $now], JSON_THROW_ON_ERROR), LOCK_EX) !== false;
    }

    /** True while another session is working through the installer (its claim is younger than CLAIM_TTL). */
    public static function claimedByOther(string $sessionId, int $now): bool
    {
        $file = Paths::storage('install.claim');
        if (!is_file($file)) {
            return false;
        }
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) && is_string($data['session'] ?? null) && is_int($data['at'] ?? null)
            && $now - $data['at'] < self::CLAIM_TTL && !hash_equals($data['session'], hash('sha256', $sessionId));
    }
}
