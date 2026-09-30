<?php

declare(strict_types=1);

namespace Gate\Security;

/** Client IP resolution (trusting X-Forwarded-For only from configured proxies) and allowlist matching. */
final class IpAddress
{
    /**
     * @param array<string, mixed> $server
     * @param list<string> $trustedProxies IPs or CIDR ranges
     */
    public static function client(array $server, array $trustedProxies): string
    {
        $remote = is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : '0.0.0.0';
        if ($trustedProxies === [] || !self::matchesAny($remote, $trustedProxies)) {
            return self::valid($remote) ? $remote : '0.0.0.0';
        }
        $forwarded = is_string($server['HTTP_X_FORWARDED_FOR'] ?? null) ? $server['HTTP_X_FORWARDED_FOR'] : '';
        $chain = array_reverse(array_map('trim', explode(',', $forwarded)));
        foreach ($chain as $ip) {
            if (self::valid($ip) && !self::matchesAny($ip, $trustedProxies)) {
                return $ip;
            }
        }
        return $remote;
    }

    public static function valid(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /** Packed binary form for VARBINARY(16) columns (null when invalid). */
    public static function toBinary(string $ip): ?string
    {
        $packed = self::valid($ip) ? inet_pton($ip) : false;
        return $packed === false ? null : $packed;
    }

    public static function fromBinary(?string $binary): string
    {
        if ($binary === null || $binary === '') {
            return '';
        }
        $ip = inet_ntop($binary);
        return $ip === false ? '' : $ip;
    }

    /**
     * Parses an allowlist: one entry per line or separated by commas; each an IP or CIDR range.
     *
     * @return list<string> valid entries
     */
    public static function parseList(string $text): array
    {
        $entries = preg_split('/[\s,;]+/', trim($text)) ?: [];
        return array_values(array_filter($entries, static fn (string $e): bool => $e !== '' && self::validEntry($e)));
    }

    public static function validEntry(string $entry): bool
    {
        if (!str_contains($entry, '/')) {
            return self::valid($entry);
        }
        [$net, $bits] = explode('/', $entry, 2);
        if (!self::valid($net) || !ctype_digit($bits)) {
            return false;
        }
        $max = str_contains($net, ':') ? 128 : 32;
        return (int) $bits <= $max;
    }

    /** @param list<string> $entries */
    public static function matchesAny(string $ip, array $entries): bool
    {
        foreach ($entries as $entry) {
            if (self::matches($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    public static function matches(string $ip, string $entry): bool
    {
        $ipBin = self::toBinary($ip);
        if ($ipBin === null) {
            return false;
        }
        if (!str_contains($entry, '/')) {
            $entryBin = self::toBinary($entry);
            return $entryBin !== null && hash_equals($entryBin, $ipBin);
        }
        [$net, $bitsText] = explode('/', $entry, 2);
        $netBin = self::toBinary($net);
        if ($netBin === null || strlen($netBin) !== strlen($ipBin) || !ctype_digit($bitsText)) {
            return false;
        }
        $bits = (int) $bitsText;
        $bytes = intdiv($bits, 8);
        if (strncmp($ipBin, $netBin, $bytes) !== 0) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
    }
}
