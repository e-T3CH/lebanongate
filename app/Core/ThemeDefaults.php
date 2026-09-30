<?php

declare(strict_types=1);

namespace Gate\Core;

/** Design colours of the website (c-*) and the admin panel (a-*). Seeded as theme.<name> settings. */
final class ThemeDefaults
{
    public const COLORS = [
        // Website (docs/GATE-UI-STRUCTURE.md §7): the logo blue for large surfaces and decoration, darker shades of the
        // same hue for text-size links and buttons (WCAG AA), one deep band colour, and a single accent.
        'c-brand' => '#5091CD',
        'c-primary' => '#2F6FA8',
        'c-primary-hover' => '#245A8C',
        'c-band' => '#173F66',
        'c-tint' => '#EAF2FA',
        'c-accent' => '#3E8E5E',
        'c-page' => '#FFFFFF',
        'c-surface' => '#F5F8FB',
        'c-line' => '#DDE6EF',
        'c-text-1' => '#12263A',
        'c-text-2' => '#3C4F63',
        'c-text-3' => '#5E7185',
        'c-footer' => '#0F2C49',
        'c-footer-text' => '#B9CADB',
        // Admin panel
        'a-bg' => '#F4F7FB',
        'a-card' => '#FFFFFF',
        'a-border' => '#E2E9F0',
        'a-divider' => '#EDF1F5',
        'a-input' => '#C9D4E0',
        'a-control' => '#D5DEE7',
        'a-dd-border' => '#DCE4EC',
        'a-dd-hover' => '#EEF4FA',
        'a-sidebar' => '#0F2C49',
        'a-sidebar-hover' => '#173F66',
        'a-sidebar-text' => '#B9CADB',
        'a-text-1' => '#0B1A2B',
        'a-text-2' => '#3A4E63',
        'a-text-3' => '#5B6F84',
        'a-placeholder' => '#8A9BAD',
        'a-primary' => '#2F6FA8',
        'a-primary-hover' => '#245A8C',
        'a-tint' => '#EAF2FA',
        'a-neutral' => '#F1F4F8',
        'a-avatar' => '#173F66',
        'a-badge' => '#2F6FA8',
        'a-success' => '#1B9E6B',
        'a-success-text' => '#1B7F57',
        'a-late' => '#B4541B',
        'a-danger' => '#E0473C',
        'a-star-off' => '#D5DEE7',
        'a-pill-new-bg' => '#EAF3FA',
        'a-pill-new' => '#245A8C',
        'a-pill-confirmed-bg' => '#E6F6EF',
        'a-pill-confirmed' => '#17734F',
        'a-pill-diagnosis-bg' => '#FFF4E0',
        'a-pill-diagnosis' => '#8A5A00',
        'a-pill-quoted-bg' => '#F0ECFB',
        'a-pill-quoted' => '#5B3FA8',
        'a-warn-bg' => '#FFF8EB',
        'a-warn-border' => '#F3DDB0',
        'a-warn-tile' => '#FDEBC8',
        'a-warn-title' => '#5E3E00',
        'a-warn-text' => '#6E5220',
        'a-danger-bg' => '#FDECEA',
        'a-danger-text' => '#B4231B',
        'a-info-bg' => '#EAF3FA',
    ];

    /** Corner radii (Appearance → radius). */
    public const RADII = [
        'r-btn' => '10px',
        'r-card' => '16px',
        'r-panel' => '20px',
        'r-dd' => '12px',
        'r-pop' => '14px',
        'r-pill' => '999px',
    ];

    /**
     * Motion tokens (design/DESIGN-SPEC.md "Motion"): standard value and the "subtle" intensity value.
     * Subtle shortens reveal distances and durations; micro-interactions (popovers, hovers, header) keep their timing.
     * When a standard value is changed in the settings, the subtle value scales by the same ratio.
     */
    public const MOTION = [
        'dur-pop' => ['150ms', null],
        'dur-pop-out' => ['120ms', null],
        'dur-hover' => ['200ms', null],
        'dur-s' => ['300ms', null],
        'dur-m' => ['700ms', '500ms'],
        'dur-h1' => ['800ms', '600ms'],
        'dur-draw' => ['1800ms', '1300ms'],
        'dur-scan' => ['1100ms', '800ms'],
        'stagger' => ['70ms', '49ms'],
        'stagger-dense' => ['55ms', '38ms'],
        'stagger-cap' => ['330ms', '230ms'],
        'rise' => ['22px', '12px'],
    ];

    /** Easing curves. */
    public const EASINGS = [
        'ease-reveal' => 'cubic-bezier(.2, .7, .2, 1)',
        'ease-out' => 'cubic-bezier(.16, 1, .3, 1)',
        'ease-in-out' => 'cubic-bezier(.65, 0, .35, 1)',
        'ease-drawer' => 'cubic-bezier(.7, 0, .3, 1)',
    ];
}
