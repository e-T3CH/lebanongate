<?php

declare(strict_types=1);

namespace BMMatic\Core;

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
        'pages',
        'page_translations',
        'page_sections',
        'page_section_translations',
        'services',
        'service_translations',
        'transmission_types',
        'transmission_type_translations',
        'process_steps',
        'process_step_translations',
        'stats',
        'stat_translations',
        'partners',
        'partner_translations',
        'appointments',
        'appointment_notes',
        'appointment_status_emails',
        'mail_queue',
        'redirects',
        'user_invitations',
        'email_changes',
        'password_resets',
        'media',
        'media_translations',
        'google_reviews',
        'review_sync_log',
    ];

    public static function isAllowed(string $table): bool
    {
        return in_array($table, self::ALLOWED, true);
    }
}
