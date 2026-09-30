<?php

declare(strict_types=1);

namespace BMMatic\Site;

use BMMatic\Core\Paths;
use BMMatic\Services\Settings;

/**
 * The logos and favicon chosen in Appearance, with the approved design's files as the fallback.
 *
 * The website is dark from top to bottom (header, page, footer), so it shows the "logo for dark backgrounds" when one
 * is set, else the main logo. The main logo is what leaves the site: search results (JSON-LD) and link previews.
 * A choice only counts when it is an image of the media library that still exists.
 */
final class Brand
{
    public const LOGO = '/assets/img/bmmatic-logo.png';
    public const FAVICON = '/assets/img/favicon-32.png';
    public const TOUCH_ICON = '/assets/img/apple-touch-icon.png';

    public function __construct(private readonly Settings $settings)
    {
    }

    /** The logo shown on the website (all of it has a dark background). */
    public function siteLogo(): string
    {
        return $this->upload('appearance.logo_dark') ?? $this->logo();
    }

    /** The main logo: structured data and link previews. */
    public function logo(): string
    {
        return $this->upload('appearance.logo') ?? self::LOGO;
    }

    /** @return array{icon: string, touch: string} */
    public function favicon(): array
    {
        $chosen = $this->upload('appearance.favicon');
        return ['icon' => $chosen ?? self::FAVICON, 'touch' => $chosen ?? self::TOUCH_ICON];
    }

    private function upload(string $key): ?string
    {
        $path = trim($this->settings->string($key));
        if (preg_match('#^/uploads/[a-f0-9]{16,64}\.(png|jpe?g|webp)$#', $path) !== 1) {
            return null;
        }
        return is_file(Paths::publicDir(ltrim($path, '/'))) ? $path : null;
    }
}
