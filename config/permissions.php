<?php

declare(strict_types=1);

/*
 * Roles and permissions — the single source of truth for the admin panel.
 *
 * Routes carry a `perm:<name>` flag (app/Http/AdminRoutes.php) and the App checks it before the controller runs;
 * views ask the same question with $view->can('<name>'), and the sidebar menu hides what a role cannot open.
 * A role that holds '*' may do everything.
 *
 * Adding a permission: add it to `permissions` (the label key is used in the Users screen), give it to the roles
 * that should have it, and use it on the route and in the view.
 */
return [
    // Permission => translation key of its description (admin.permissions.*)
    'permissions' => [
        'dashboard.view' => 'admin.permissions.dashboard_view',
        'appointments.view' => 'admin.permissions.appointments_view',
        'appointments.manage' => 'admin.permissions.appointments_manage',
        'content.view' => 'admin.permissions.content_view',
        'content.edit' => 'admin.permissions.content_edit',
        'media.view' => 'admin.permissions.media_view',
        'media.manage' => 'admin.permissions.media_manage',
        'reviews.manage' => 'admin.permissions.reviews_manage',
        'appearance.manage' => 'admin.permissions.appearance_manage',
        'languages.manage' => 'admin.permissions.languages_manage',
        'users.manage' => 'admin.permissions.users_manage',
        'settings.manage' => 'admin.permissions.settings_manage',
        'security.manage' => 'admin.permissions.security_manage',
    ],

    // Role => permissions ('*' = everything). Roles match the `role` column of the users table.
    'roles' => [
        'admin' => ['*'],
        'editor' => [
            'dashboard.view',
            'appointments.view',
            'appointments.manage',
            'content.view',
            'content.edit',
            'media.view',
            'media.manage',
            'reviews.manage',
        ],
    ],
];
