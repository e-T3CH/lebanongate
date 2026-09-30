<?php

declare(strict_types=1);

namespace BMMatic\Mail;

/** An outgoing email (plain text with an optional HTML alternative). */
final class MailMessage
{
    public function __construct(
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $text,
        public readonly ?string $html = null,
        public readonly string $replyTo = '',
    ) {
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid recipient address.');
        }
        if (preg_match('/[\r\n]/', $subject . $toName . $replyTo) === 1) {
            throw new \InvalidArgumentException('Header values must not contain line breaks.');
        }
    }
}
