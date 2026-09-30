<?php

declare(strict_types=1);

namespace Gate\Repositories;

use Gate\Core\Database;
use Gate\I18n\LanguageRules;

/**
 * @phpstan-type LanguageRow array{code: string, name: string, native_name: string, is_enabled: bool, is_default: bool, sort_order: int}
 */
final class LanguageRepository
{
    /** @var list<LanguageRow>|null */
    private ?array $cache = null;

    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<LanguageRow> ordered by sort_order */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $this->cache = array_map(static fn (array $r): array => [
            'code' => (string) $r['code'],
            'name' => (string) $r['name'],
            'native_name' => (string) $r['native_name'],
            'is_enabled' => (int) $r['is_enabled'] === 1,
            'is_default' => (int) $r['is_default'] === 1,
            'sort_order' => (int) $r['sort_order'],
        ], $this->db->select('languages', [], ['*'], ['sort_order' => 'ASC', 'code' => 'ASC']));
        return $this->cache;
    }

    /** @return list<LanguageRow> */
    public function enabled(): array
    {
        return array_values(array_filter($this->all(), static fn (array $l): bool => $l['is_enabled']));
    }

    /** @return list<string> */
    public function enabledCodes(): array
    {
        return array_column($this->enabled(), 'code');
    }

    public function defaultCode(): string
    {
        foreach ($this->all() as $lang) {
            if ($lang['is_default']) {
                return $lang['code'];
            }
        }
        return $this->enabled()[0]['code'] ?? 'en';
    }

    /**
     * Applies enabled flags and the default language after validating the rules
     * (default must be enabled; at least one language enabled).
     *
     * @param list<string> $enabledCodes
     */
    public function saveState(array $enabledCodes, string $defaultCode): void
    {
        $known = array_column($this->all(), 'code');
        $errors = LanguageRules::validate($known, $enabledCodes, $defaultCode);
        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
        $this->db->transaction(function (Database $db) use ($known, $enabledCodes, $defaultCode): void {
            foreach ($known as $code) {
                $db->update('languages', [
                    'is_enabled' => in_array($code, $enabledCodes, true) ? 1 : 0,
                    'is_default' => $code === $defaultCode ? 1 : 0,
                ], ['code' => $code]);
            }
        });
        $this->cache = null;
    }
}
