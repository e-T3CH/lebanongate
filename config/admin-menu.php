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
            ['key' => 'messages', 'label' => 'admin.nav.messages', 'icon' => 'fa-regular fa-envelope', 'path' => 'messages', 'route' => 'admin.messages', 'permission' => 'messages.view', 'badge' => 'messages.unread'],
            ['key' => 'subscribers', 'label' => 'admin.nav.subscribers', 'icon' => 'fa-solid fa-at', 'path' => 'subscribers', 'route' => 'admin.subscribers', 'permission' => 'subscribers.manage'],
        ],
    ],
    [
        'section' => 'content',
        'items' => [
            ['key' => 'pages', 'label' => 'admin.nav.pages', 'icon' => 'fa-regular fa-file-lines', 'path' => 'pages', 'route' => 'admin.pages', 'permission' => 'content.view'],
            ['key' => 'expertise', 'label' => 'admin.nav.expertise', 'icon' => 'fa-solid fa-layer-group', 'path' => 'expertise', 'route' => 'admin.expertise', 'permission' => 'content.view'],
            ['key' => 'project', 'label' => 'admin.nav.projects', 'icon' => 'fa-solid fa-diagram-project', 'path' => 'projects', 'route' => 'admin.entries.project', 'permission' => 'content.view'],
            ['key' => 'news', 'label' => 'admin.nav.news', 'icon' => 'fa-regular fa-newspaper', 'path' => 'news', 'route' => 'admin.entries.news', 'permission' => 'content.view'],
            ['key' => 'publication', 'label' => 'admin.nav.publications', 'icon' => 'fa-regular fa-file-pdf', 'path' => 'publications', 'route' => 'admin.entries.publication', 'permission' => 'content.view'],
            ['key' => 'album', 'label' => 'admin.nav.albums', 'icon' => 'fa-regular fa-images', 'path' => 'albums', 'route' => 'admin.entries.album', 'permission' => 'content.view'],
            ['key' => 'partners', 'label' => 'admin.nav.partners', 'icon' => 'fa-regular fa-handshake', 'path' => 'content/partners', 'route' => 'admin.content.list', 'permission' => 'content.view'],
            ['key' => 'stats', 'label' => 'admin.nav.stats', 'icon' => 'fa-solid fa-chart-simple', 'path' => 'content/stats', 'route' => 'admin.content.list', 'permission' => 'content.view'],
            ['key' => 'media', 'label' => 'admin.nav.media', 'icon' => 'fa-regular fa-image', 'path' => 'media', 'route' => 'admin.media', 'permission' => 'media.view'],
            ['key' => 'texts', 'label' => 'admin.nav.texts', 'icon' => 'fa-solid fa-language', 'path' => 'website-texts', 'route' => 'admin.texts', 'permission' => 'content.view'],
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
