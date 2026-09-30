<?php

declare(strict_types=1);

namespace BMMatic\Security;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use BMMatic\Core\Clock;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238, 30 s, 6 digits, SHA-1 for authenticator-app compatibility) and recovery codes.
 * Secrets are stored encrypted (Crypto); recovery codes are stored as HMAC-SHA256 hashes and shown only once.
 */
final class TwoFactor
{
    public const RECOVERY_CODE_COUNT = 10;
    /** Accept codes from one step before/after the current one (clock drift). */
    private const WINDOW = 1;
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private readonly Google2FA $google2fa;

    public function __construct(private readonly Crypto $crypto, private readonly Clock $clock)
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function otpauthUri(string $issuer, string $account, #[\SensitiveParameter] string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($issuer, $account, $secret);
    }

    /** Inline SVG QR code (no external service, no data: URI, CSP-safe). */
    public function qrSvg(string $issuer, string $account, #[\SensitiveParameter] string $secret, int $size = 200): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));
        $svg = $writer->writeString($this->otpauthUri($issuer, $account, $secret));
        return (string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    public function currentTimestep(): int
    {
        return intdiv($this->clock->now()->getTimestamp(), 30);
    }

    /**
     * Verifies a 6-digit code. Returns the matched timestep (to be stored, so the same code cannot be replayed),
     * or null when the code is invalid or not newer than $lastTimestep.
     */
    public function verify(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code, ?int $lastTimestep): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }
        try {
            // Passing a previous timestep (0 when none) makes google2fa return the matched step instead of true.
            $result = $this->google2fa->verifyKeyNewer($secret, $code, $lastTimestep ?? 0, self::WINDOW, $this->currentTimestep());
        } catch (\Throwable) {
            return null;
        }
        return is_int($result) && $result > 0 ? $result : null;
    }

    /** Code for a given timestep (tests and diagnostics). */
    public function codeAt(#[\SensitiveParameter] string $secret, int $timestep): string
    {
        return (string) $this->google2fa->oathTotp($secret, $timestep);
    }

    /** @return list<string> plain codes, formatted XXXXX-XXXXX */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        $max = strlen(self::ALPHABET) - 1;
        while (count($codes) < self::RECOVERY_CODE_COUNT) {
            $raw = '';
            for ($i = 0; $i < 10; $i++) {
                $raw .= self::ALPHABET[random_int(0, $max)];
            }
            $codes[substr($raw, 0, 5) . '-' . substr($raw, 5)] = true;
        }
        return array_keys($codes);
    }

    public function hashRecoveryCode(#[\SensitiveParameter] string $code): string
    {
        return $this->crypto->hmac('recovery:' . self::normalizeRecoveryCode($code));
    }

    /**
     * @param list<string> $plainCodes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $c): string => $this->hashRecoveryCode($c), $plainCodes);
    }

    /**
     * Consumes a recovery code. Returns the remaining hashes, or null when the code does not match.
     *
     * @param list<string> $hashes
     * @return list<string>|null
     */
    public function consumeRecoveryCode(array $hashes, #[\SensitiveParameter] string $code): ?array
    {
        if (strlen(self::normalizeRecoveryCode($code)) !== 10) {
            return null;
        }
        $candidate = $this->hashRecoveryCode($code);
        $matched = null;
        foreach ($hashes as $i => $hash) {
            if (hash_equals($hash, $candidate)) {
                $matched = $i;
            }
        }
        if ($matched === null) {
            return null;
        }
        unset($hashes[$matched]);
        return array_values($hashes);
    }

    public static function looksLikeRecoveryCode(string $input): bool
    {
        return strlen(self::normalizeRecoveryCode($input)) === 10;
    }

    public function encryptSecret(#[\SensitiveParameter] string $secret): string
    {
        return $this->crypto->encrypt($secret);
    }

    public function decryptSecret(string $stored): string
    {
        return $this->crypto->decrypt($stored);
    }

    private static function normalizeRecoveryCode(string $code): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }
}
