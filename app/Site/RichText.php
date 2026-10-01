<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Core\Paths;

/**
 * Rich text from the content tables (page and service bodies): sanitised with HTML Purifier to a small allowlist
 * (headings, paragraphs, lists, emphasis, links) before output, so edited content can never inject markup or styles.
 * Placeholders such as :email are replaced with escaped values first.
 */
final class RichText
{
    private static ?\HTMLPurifier $purifier = null;

    /** @param array<string, string> $replacements placeholder name => plain text */
    public static function render(string $html, array $replacements = []): string
    {
        if ($replacements !== []) {
            uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
            foreach ($replacements as $name => $value) {
                $html = str_replace(':' . $name, htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), $html);
            }
        }
        return self::withBase(self::purifier()->purify($html));
    }

    /**
     * Sanitises what an editor submits, before it is stored. Rendering purifies again, so content that was stored
     * before a rule changed (or edited straight in the database) can still never inject markup.
     */
    public static function sanitize(string $html): string
    {
        return self::purifier()->purify($html);
    }

    private static function purifier(): \HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'h2,h3,p,br,ul,ol,li,strong,em,a[href]');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $config->set('Attr.AllowedFrameTargets', []);
            $config->set('AutoFormat.RemoveEmpty', true);
            $cache = Paths::storage('cache/htmlpurifier');
            if ((is_dir($cache) || @mkdir($cache, 0700, true)) && is_writable($cache)) {
                $config->set('Cache.SerializerPath', $cache);
            } else {
                $config->set('Cache.DefinitionImpl', null);
            }
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier;
    }

    /**
     * Links and images inside rich text are stored root-relative ('/uploads/…', '/en/projects'); in a site installed
     * in a folder they need the folder in front (Url::to). Runs on purified HTML, whose attributes are double-quoted.
     */
    private static function withBase(string $html): string
    {
        if (\Gate\Core\Url::base() === '') {
            return $html;
        }
        return (string) preg_replace_callback(
            '/\b(href|src)="(\/(?!\/)[^"]*)"/',
            static fn (array $m): string => $m[1] . '="' . htmlspecialchars(\Gate\Core\Url::to(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '"',
            $html,
        );
    }
}
