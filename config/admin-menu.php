<?php

declare(strict_types=1);

/*
 * Admin sidebar menu: sections, items, icons, badges and role permissions (one place for the whole menu).
 *
 * section  translation key suffix: admin.nav.section_<section>
 * key      identifier (the active item of a screen)
 * label    translation key
 * icon     Font Awesome classes (regular style where Font Awesome Free has the icon)
 * path     path below the admin prefix ('' = dashboard)
 * route    route name; items whose route is not registered yet (module of a later phase) are not shown
 * permission  permission needed to see and open the item (config/permissions.php)
 * badge    optional counter key, filled by the controller (for example unread messages)
 */
return [
    [
        'section' => 'main',
        'items' => [
            ['key' => 'dashboard', 'label' => 'admin.nav.dashboard', 'icon' => 'fa-solid fa-table-cells-large', 'path' => '', 'route' => 'admin.dashboard', 'permission' => 'dashboard.view'],
            ['key' => 'appointments', 'label' => 'admin.nav.appointments', 'icon' => 'fa-regular fa-calendar', 'path' => 'appointments', 'route' => 'admin.appointments', 'permission' => 'appointments.view', 'badge' => 'appointments.new'],
            ['key' => 'messages', 'label' => 'admin.nav.messages', 'icon' => 'fa-regular fa-envelope', 'path' => 'messages', 'route' => 'admin.messages', 'permission' => 'appointments.view', 'badge' => 'messages.unread'],
        ],
    ],
    [
        'section' => 'content',
        'items' => [
            ['key' => 'services', 'label' => 'admin.nav.services', 'icon' => 'fa-solid fa-wrench', 'path' => 'services', 'route' => 'admin.services', 'permission' => 'content.view'],
            ['key' => 'pages', 'label' => 'admin.nav.pages', 'icon' => 'fa-regular fa-file-lines', 'path' => 'pages', 'route' => 'admin.pages', 'permission' => 'content.view'],
            ['key' => 'reviews', 'label' => 'admin.nav.reviews', 'icon' => 'fa-regular fa-star', 'path' => 'reviews', 'route' => 'admin.reviews', 'permission' => 'reviews.manage'],
            ['key' => 'media', 'label' => 'admin.nav.media', 'icon' => 'fa-regular fa-image', 'path' => 'media', 'route' => 'admin.media', 'permission' => 'media.view'],
        ],
    ],
    [
        'section' => 'system',
        'items' => [
            ['key' => 'languages', 'label' => 'admin.nav.languages', 'icon' => 'fa-solid fa-globe', 'path' => 'settings/languages', 'route' => 'admin.languages', 'permission' => 'languages.manage'],
            ['key' => 'appearance', 'label' => 'admin.nav.appearance', 'icon' => 'fa-solid fa-palette', 'path' => 'appearance', 'route' => 'admin.appearance', 'permission' => 'appearance.manage'],
            ['key' => 'users', 'label' => 'admin.nav.users', 'icon' => 'fa-solid fa-user-group', 'path' => 'users', 'route' => 'admin.users', 'permission' => 'users.manage'],
            ['key' => 'security', 'label' => 'admin.nav.security', 'icon' => 'fa-solid fa-shield-halved', 'path' => 'security', 'route' => 'admin.security', 'permission' => 'security.manage'],
            ['key' => 'settings', 'label' => 'admin.nav.settings', 'icon' => 'fa-solid fa-gear', 'path' => 'settings', 'route' => 'admin.settings', 'permission' => 'settings.manage'],
        ],
    ],
];
