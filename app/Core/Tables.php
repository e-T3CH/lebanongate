<?php

declare(strict_types=1);

namespace Gate\Core;

/**
 * Table whitelist. Every table name that reaches SQL passes through here; anything else throws.
 * A migration that creates a table must add its name to this list.
 */
final class Tables
{
    public const ALLOWED = [
        'migrations',
        'settings',
        'languages',
        'ui_translations',
        'users',
        'rate_limits',
        'audit_log',
        'media',
        'media_translations',
        'pages',
        'page_translations',
        'page_sections',
        'page_section_translations',
        'expertise',
        'expertise_translations',
        'entries',
        'entry_translations',
        'entry_media',
        'stats',
        'stat_translations',
        'partners',
        'partner_translations',
        'mail_queue',
        'redirects',
        'user_invitations',
        'email_changes',
        'password_resets',
        'messages',
        'message_notes',
        'subscribers',
    ];

    public static function isAllowed(string $table): bool
    {
        return in_array($table, self::ALLOWED, true);
    }
}
