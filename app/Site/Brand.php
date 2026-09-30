<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Core\Paths;
use Gate\Services\Settings;

/**
 * The logos and favicon chosen in Appearance. Until a logo is uploaded, the header and footer show the placeholder
 * slot of the approved mockup (the cedar mark with the name), and structured data uses the cedar mark image.
 *
 * The header is light, so it shows the main logo; the footer is dark, so it shows the "logo for dark backgrounds"
 * when one is set, else the main logo. A choice only counts when it is an image of the media library that exists.
 */
final class Brand
{
    public const MARK = '/assets/img/gate-mark.png';
    public const FAVICON = '/assets/img/favicon-32.png';
    public const TOUCH_ICON = '/assets/img/apple-touch-icon.png';
    /** The developer credit in the footer ("Developed by"): the logo version for dark backgrounds (asset path). */
    public const CREDIT_LOGO = 'img/e5hop-logo-light.png';
    /** Dark letters, for the light backgrounds of the admin panel, sign-in, installer and error pages. */
    public const CREDIT_LOGO_DARK = 'img/e5hop-logo-dark.png';
    public const CREDIT_URL = 'https://www.e-5hop.com';

    public function __construct(private readonly Settings $settings)
    {
    }

    /** The uploaded logo for the (light) header, or null for the placeholder. */
    public function headerLogo(): ?string
    {
        return $this->upload('appearance.logo');
    }

    /** The uploaded logo for the (dark) footer, or null for the placeholder. */
    public function footerLogo(): ?string
    {
        return $this->upload('appearance.logo_dark') ?? $this->upload('appearance.logo');
    }

    /** The main logo for structured data and link previews: the uploaded one, else the cedar mark. */
    public function logo(): string
    {
        return $this->upload('appearance.logo') ?? self::MARK;
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
