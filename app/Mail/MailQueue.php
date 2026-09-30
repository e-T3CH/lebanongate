<?php

declare(strict_types=1);

namespace Gate\Mail;

use Gate\Core\Clock;
use Gate\Core\Database;

/**
 * Database-backed outbox. A form stores its emails here and returns immediately; the worker delivers them after the
 * response (or from `bin/console mail:work`). Messages are claimed with a lock so parallel workers never send twice;
 * failures are retried with backoff and marked failed after MAX_ATTEMPTS.
 */
final class MailQueue
{
    public const MAX_ATTEMPTS = 5;
    /** Minutes to wait before attempt 2, 3, 4, 5. */
    private const BACKOFF = [1, 5, 30, 120];

    public function __construct(private readonly Database $db, private readonly Clock $clock)
    {
    }

    public function enqueue(MailMessage $message): int
    {
        $now = $this->now();
        return $this->db->insert('mail_queue', [
            'to_email' => $message->toEmail,
            'to_name' => $message->toName,
            'reply_to' => $message->replyTo,
            'subject' => $message->subject,
            'body_text' => $message->text,
            'body_html' => $message->html,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => $now,
            'created_at' => $now,
        ]);
    }

    public function hasDue(): bool
    {
        return $this->db->scalar("SELECT 1 FROM {mail_queue} WHERE `status` = 'pending' AND `available_at` <= :now AND (`locked_until` IS NULL OR `locked_until` < :now2) LIMIT 1", ['now' => $this->now(), 'now2' => $this->now()]) !== null;
    }

    /**
     * Claims up to $limit due messages for $lockSeconds.
     *
     * @return list<array{id: int, message: MailMessage, attempts: int}>
     */
    public function claim(int $limit, int $lockSeconds = 120): array
    {
        $now = $this->now();
        $rows = $this->db->all("SELECT * FROM {mail_queue} WHERE `status` = 'pending' AND `available_at` <= :now AND (`locked_until` IS NULL OR `locked_until` < :now2) ORDER BY `id` LIMIT " . max(1, $limit), ['now' => $now, 'now2' => $now]);
        $until = $this->clock->now()->modify('+' . $lockSeconds . ' seconds')->format('Y-m-d H:i:s');
        $claimed = [];
        foreach ($rows as $row) {
            $got = $this->db->run("UPDATE {mail_queue} SET `locked_until` = :until WHERE `id` = :id AND `status` = 'pending' AND (`locked_until` IS NULL OR `locked_until` < :now)", ['until' => $until, 'id' => (int) $row['id'], 'now' => $now])->rowCount();
            if ($got !== 1) {
                continue;
            }
            try {
                $message = new MailMessage((string) $row['to_email'], (string) $row['to_name'], (string) $row['subject'], (string) $row['body_text'], is_string($row['body_html']) ? $row['body_html'] : null, (string) $row['reply_to']);
            } catch (\InvalidArgumentException $e) {
                $this->db->update('mail_queue', ['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 500), 'locked_until' => null], ['id' => (int) $row['id']]);
                continue;
            }
            $claimed[] = ['id' => (int) $row['id'], 'message' => $message, 'attempts' => (int) $row['attempts']];
        }
        return $claimed;
    }

    public function markSent(int $id): void
    {
        $this->db->update('mail_queue', ['status' => 'sent', 'sent_at' => $this->now(), 'locked_until' => null, 'attempts' => (int) $this->db->scalar('SELECT `attempts` FROM {mail_queue} WHERE `id` = :id', ['id' => $id]) + 1], ['id' => $id]);
    }

    public function markFailed(int $id, int $previousAttempts, string $error): void
    {
        $attempts = $previousAttempts + 1;
        $final = $attempts >= self::MAX_ATTEMPTS;
        $wait = self::BACKOFF[min($attempts - 1, count(self::BACKOFF) - 1)];
        $this->db->update('mail_queue', [
            'status' => $final ? 'failed' : 'pending',
            'attempts' => $attempts,
            'available_at' => $this->clock->now()->modify('+' . $wait . ' minutes')->format('Y-m-d H:i:s'),
            'locked_until' => null,
            'last_error' => mb_substr($error, 0, 500),
        ], ['id' => $id]);
    }

    /** @return array{pending: int, sent: int, failed: int} */
    public function counts(): array
    {
        $out = ['pending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($this->db->all('SELECT `status`, COUNT(*) AS n FROM {mail_queue} GROUP BY `status`') as $row) {
            if (isset($out[(string) $row['status']])) {
                $out[(string) $row['status']] = (int) $row['n'];
            }
        }
        return $out;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
