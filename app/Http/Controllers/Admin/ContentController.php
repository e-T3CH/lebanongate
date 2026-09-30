<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Repositories\ContentAdminRepository;
use Gate\Site\RichText;

/**
 * Shared ground for the content modules: the language tabs (EN/FR/NL with their translation state), the slug rules
 * and the sanitising of rich text on save. Rendering purifies again, so stored content can never inject markup.
 */
abstract class ContentController extends AdminController
{
    protected function content(): ContentAdminRepository
    {
        return new ContentAdminRepository($this->app->db(), $this->app->clock);
    }

    /** The language being edited: ?lang=xx when it is enabled, else the default language. */
    protected function editLang(string $requested): string
    {
        $languages = $this->app->languages();
        return in_array($requested, $languages->enabledCodes(), true) ? $requested : $languages->defaultCode();
    }

    /**
     * Language tabs for one editor screen.
     *
     * @param array<string, array<string, mixed>> $translations language => row
     * @return list<array{label: string, href: string, active: bool}>
     */
    protected function languageTabs(string $baseUrl, string $current, array $translations, bool $withPublishState = true): array
    {
        $tabs = [];
        foreach ($this->app->languages()->enabled() as $language) {
            $code = (string) $language['code'];
            $row = $translations[$code] ?? null;
            $mark = '';
            if ($withPublishState) {
                $mark = $row === null ? ' ·' : ((int) ($row['is_published'] ?? 1) === 1 ? '' : ' ·');
            }
            $tabs[] = [
                'label' => strtoupper($code) . $mark,
                'href' => $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'lang=' . $code,
                'active' => $code === $current,
            ];
        }
        return $tabs;
    }

    /**
     * A URL slug: lower case, words separated by hyphens, no accents. Empty is allowed (the home page).
     */
    protected static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = is_string($ascii) ? $ascii : $value;
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    /** Rich text as it goes into the database: sanitised to the allowlist (h2, h3, p, br, lists, strong, em, a). */
    protected static function richText(string $html): string
    {
        return RichText::sanitize(mb_substr($html, 0, 60000));
    }

    /** One line of plain text: control characters removed, whitespace collapsed, length limited. */
    protected static function line(string $value, int $max = 200): string
    {
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $value)), 0, $max);
    }

    /** Several lines of plain text (intro, summary): line breaks kept. */
    protected static function text(string $value, int $max = 2000): string
    {
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return mb_substr(trim(str_replace("\r\n", "\n", $value)), 0, $max);
    }

    /** @return array<string, mixed> */
    protected function pullArray(string $key): array
    {
        $value = $this->app->session()->pull($key);
        return is_array($value) ? $value : [];
    }
}
