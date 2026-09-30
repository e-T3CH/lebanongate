<?php

declare(strict_types=1);

namespace BMMatic\Services;

use BMMatic\Core\Clock;
use BMMatic\Core\Database;
use BMMatic\Security\IpAddress;

/**
 * Security audit trail: logins, failures, lockouts, 2FA changes, settings and user changes, installation.
 * Context values must not contain secrets (passwords, codes, keys).
 */
final class AuditLog
{
    public const LOGIN_SUCCESS = 'auth.login.success';
    public const LOGIN_FAILED = 'auth.login.failed';
    public const LOGIN_LOCKED = 'auth.login.locked';
    public const LOGOUT = 'auth.logout';
    public const SESSION_EXPIRED = 'auth.session.expired';
    public const TWO_FACTOR_PASSED = 'auth.2fa.passed';
    public const TWO_FACTOR_FAILED = 'auth.2fa.failed';
    public const TWO_FACTOR_LOCKED = 'auth.2fa.locked';
    public const RECOVERY_CODE_USED = 'auth.2fa.recovery_code_used';
    public const TWO_FACTOR_ENABLED = 'security.2fa.enabled';
    public const TWO_FACTOR_DISABLED = 'security.2fa.disabled';
    public const TWO_FACTOR_DISABLE_FAILED = 'security.2fa.disable_failed';
    public const RECOVERY_CODES_REGENERATED = 'security.2fa.recovery_codes_regenerated';
    public const SETTINGS_CHANGED = 'settings.changed';
    public const USER_CREATED = 'user.created';
    public const USER_INVITED = 'user.invited';
    public const USER_INVITE_ACCEPTED = 'user.invite_accepted';
    public const USER_INVITE_CANCELLED = 'user.invite_cancelled';
    public const USER_DEACTIVATED = 'user.deactivated';
    public const USER_ACTIVATED = 'user.activated';
    public const EMAIL_CHANGE_REQUESTED = 'user.email_change_requested';
    public const EMAIL_CHANGED = 'user.email_changed';
    public const PERMISSION_DENIED = 'security.permission_denied';
    public const APPOINTMENT_STATUS_CHANGED = 'appointment.status_changed';
    public const APPOINTMENT_MARKED_UNREAD = 'appointment.marked_unread';
    public const APPOINTMENT_NOTE_ADDED = 'appointment.note_added';
    public const APPOINTMENT_NOTE_DELETED = 'appointment.note_deleted';
    public const APPOINTMENT_EXPORTED = 'appointment.exported';
    public const CONTENT_CHANGED = 'content.changed';
    public const CONTENT_PUBLISHED = 'content.published';
    public const CONTENT_REORDERED = 'content.reordered';
    public const MEDIA_UPLOADED = 'media.uploaded';
    public const MEDIA_REPLACED = 'media.replaced';
    public const MEDIA_DELETED = 'media.deleted';
    public const MEDIA_REJECTED = 'media.rejected';
    public const REVIEWS_SYNCED = 'reviews.synced';
    public const REVIEWS_IMPORTED = 'reviews.imported';
    public const REVIEW_VISIBILITY_CHANGED = 'reviews.visibility_changed';
    public const BACKUP_RESTORED = 'backup.restored';
    public const USER_CHANGED = 'user.changed';
    public const PASSWORD_RESET = 'user.password_reset';
    public const PASSWORD_RESET_REQUESTED = 'user.password_reset_requested';
    public const SCHEDULER_RUN = 'system.scheduler_run';
    public const BACKUP_CREATED = 'backup.created';
    public const BACKUP_DOWNLOADED = 'backup.downloaded';
    public const SYSTEM_UPDATED = 'system.updated';
    public const ADMIN_PATH_SHOWN = 'security.admin_path_shown';
    public const ADMIN_PATH_REGENERATED = 'security.admin_path_regenerated';
    public const LOCKOUT_CLEARED = 'security.lockout_cleared';
    public const ADMIN_IP_BLOCKED = 'security.admin_ip_blocked';
    public const CSRF_FAILED = 'security.csrf_failed';
    public const INSTALLED = 'system.installed';

    private string $ip = '';
    private string $userAgent = '';

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    public function setRequestContext(string $ip, string $userAgent): void
    {
        $this->ip = $ip;
        $this->userAgent = mb_substr($userAgent, 0, 255);
    }

    /** @param array<string, mixed> $context */
    public function record(string $event, ?int $userId = null, array $context = []): void
    {
        $this->db->insert('audit_log', [
            'event' => mb_substr($event, 0, 64),
            'user_id' => $userId,
            'ip' => $this->ip === '' ? null : IpAddress::toBinary($this->ip),
            'user_agent' => $this->userAgent === '' ? null : $this->userAgent,
            'context' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
            'created_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<array<string, mixed>> newest first */
    public function latest(int $limit = 50, ?string $event = null): array
    {
        $rows = $this->db->select('audit_log', $event === null ? [] : ['event' => $event], ['*'], ['id' => 'DESC'], $limit);
        foreach ($rows as &$row) {
            $row['ip'] = IpAddress::fromBinary(is_string($row['ip']) ? $row['ip'] : null);
        }
        return $rows;
    }
}
