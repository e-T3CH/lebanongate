<?php

declare(strict_types=1);

namespace BMMatic\Core;

/** Design colors (DESIGN-SPEC.md, mockups/src/css/tokens.css). Seeded as theme.<name> settings. */
final class ThemeDefaults
{
    public const COLORS = [
        'c-page' => '#07111D',
        'c-topbar' => '#06101B',
        'c-footer' => '#050D17',
        'c-surface' => '#0A1726',
        'c-card' => '#0C1B2D',
        'c-tile' => '#10263E',
        'c-badge' => '#12304D',
        'c-map' => '#081421',
        'c-line-1' => '#13263B',
        'c-line-2' => '#1B324C',
        'c-line-3' => '#1E3752',
        'c-line-4' => '#22405F',
        'c-line-5' => '#2A4563',
        'c-white' => '#FFFFFF',
        'c-text-1' => '#E9F0F7',
        'c-text-2' => '#C9D6E3',
        'c-text-3' => '#A9BBCC',
        'c-text-4' => '#93A8BD',
        'c-text-5' => '#7F95AB',
        'c-text-6' => '#6F86A0', // accessibility correction, was #5E7893
        'c-text-7' => '#6F86A0',
        'c-text-8' => '#6F86A0', // accessibility correction, was #4F6780
        'c-primary' => '#0E6BA6',
        'c-primary-hover' => '#147BBD', // accessibility correction, was #1580C4
        'c-accent' => '#38A3E8',
        'c-accent-hover' => '#7CC4F2',
        'c-star' => '#F5B82E',
        'c-grid' => 'rgba(56, 163, 232, .06)',
        'c-grid-map' => 'rgba(56, 163, 232, .08)',
        'c-header-bg' => 'rgba(7, 17, 29, .85)',
        'c-header-bg-mobile' => 'rgba(7, 17, 29, .92)',
        'c-panel' => 'rgba(12, 27, 45, .97)',
        'c-langbtn' => 'rgba(12, 27, 45, .7)',
        'c-dock' => 'rgba(12, 27, 45, .96)',
        'c-toggle-dark' => '#243B55',
        'c-placeholder-dark' => '#6F86A0',
        'a-bg' => '#F4F7FB',
        'a-card' => '#FFFFFF',
        'a-border' => '#E2E9F0',
        'a-divider' => '#EDF1F5',
        'a-input' => '#C9D4E0',
        'a-control' => '#D5DEE7',
        'a-dd-border' => '#DCE4EC',
        'a-dd-hover' => '#EEF4FA',
        'a-sidebar' => '#0A1726',
        'a-sidebar-hover' => '#10263E',
        'a-sidebar-text' => '#93A8BD',
        'a-text-1' => '#0B1A2B',
        'a-text-2' => '#3A4E63',
        'a-text-3' => '#5B6F84',
        'a-placeholder' => '#8A9BAD',
        'a-primary' => '#0E6BA6',
        'a-primary-hover' => '#0B5A8C',
        'a-tint' => '#EAF3FA',
        'a-neutral' => '#F1F4F8',
        'a-avatar' => '#1B4A78',
        'a-badge' => '#1580C4',
        'a-success' => '#1B9E6B',
        'a-success-text' => '#1B7F57',
        'a-late' => '#B4541B',
        'a-danger' => '#E0473C',
        'a-star-off' => '#D5DEE7',
        'a-pill-new-bg' => '#EAF3FA',
        'a-pill-new' => '#0B5A8C',
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
