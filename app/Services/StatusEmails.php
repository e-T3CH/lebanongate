<?php

declare(strict_types=1);

namespace Gate\Services;

use Gate\Core\Database;
use Gate\Repositories\AppointmentRepository;

/**
 * Optional customer email per appointment status: one template per status per language, and a switch per status.
 * Everything is off by default — the workshop decides which steps are worth an email.
 * Placeholders in subject and body: :name, :car, :status, :site, :phone.
 */
final class StatusEmails
{
    public const PLACEHOLDERS = ['name', 'car', 'status', 'site', 'phone'];

    public function __construct(private readonly Database $db, private readonly Settings $settings)
    {
    }

    public function isEnabled(string $status): bool
    {
        return in_array($status, AppointmentRepository::STATUSES, true) && $this->settings->bool('appointments.status_email.' . $status, false);
    }

    /** @return list<string> the statuses that email the customer */
    public function enabledStatuses(): array
    {
        return array_values(array_filter(AppointmentRepository::STATUSES, fn (string $s): bool => $this->isEnabled($s)));
    }

    public function setEnabled(string $status, bool $enabled): void
    {
        if (in_array($status, AppointmentRepository::STATUSES, true)) {
            $this->settings->set('appointments.status_email.' . $status, $enabled, 'bool');
        }
    }

    /** @return array{subject: string, body: string}|null */
    public function template(string $status, string $lang): ?array
    {
        $row = $this->db->first('appointment_status_emails', ['status' => $status, 'lang_code' => $lang]);
        return $row === null ? null : ['subject' => (string) $row['subject'], 'body' => (string) ($row['body'] ?? '')];
    }

    /** @return array<string, array{subject: string, body: string}> language => template */
    public function templates(string $status): array
    {
        $out = [];
        foreach ($this->db->select('appointment_status_emails', ['status' => $status]) as $row) {
            $out[(string) $row['lang_code']] = ['subject' => (string) $row['subject'], 'body' => (string) ($row['body'] ?? '')];
        }
        return $out;
    }

    public function save(string $status, string $lang, string $subject, string $body): void
    {
        if (!in_array($status, AppointmentRepository::STATUSES, true)) {
            return;
        }
        $this->db->upsert('appointment_status_emails', [
            'status' => $status,
            'lang_code' => $lang,
            'subject' => mb_substr(trim((string) preg_replace('/\s+/', ' ', $subject)), 0, 200),
            'body' => mb_substr($body, 0, 4000),
        ], ['subject', 'body']);
    }
}
