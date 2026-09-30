<?php

declare(strict_types=1);

namespace Gate\Mail;

use Gate\Services\Settings;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * SMTP delivery through PHPMailer with the Email settings (mail.*). The password setting is stored encrypted.
 * Short timeouts: sending happens after the response or from the console worker, never inside a page request.
 */
final class SmtpTransport implements MailTransport
{
    /**
     * @param array{host: string, port: int, encryption: string, username: string, password: string, from_email: string, from_name: string} $config
     */
    public function __construct(private readonly array $config, private readonly int $timeout = 15)
    {
    }

    public static function fromSettings(Settings $settings): self
    {
        return new self([
            'host' => $settings->string('mail.host'),
            'port' => $settings->int('mail.port', 587),
            'encryption' => $settings->string('mail.encryption', 'tls'),
            'username' => $settings->string('mail.username'),
            'password' => $settings->string('mail.password'),
            'from_email' => $settings->string('mail.from_email'),
            'from_name' => $settings->string('mail.from_name', 'GATE Lebanon'),
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->config['host'] !== '' && filter_var($this->config['from_email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    public function send(MailMessage $message): void
    {
        if (!$this->isConfigured()) {
            throw new MailException('Email is not configured (SMTP host and sender address).');
        }
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->Timeout = $this->timeout;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
            $mail->SMTPAutoTLS = $this->config['encryption'] !== 'none';
            if ($this->config['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($this->config['encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            if ($this->config['username'] !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
            }
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($message->toEmail, $message->toName);
            if ($message->replyTo !== '' && filter_var($message->replyTo, FILTER_VALIDATE_EMAIL) !== false) {
                $mail->addReplyTo($message->replyTo);
            }
            $mail->Subject = $message->subject;
            if ($message->html !== null) {
                $mail->isHTML(true);
                $mail->Body = $message->html;
                $mail->AltBody = $message->text;
            } else {
                $mail->Body = $message->text;
            }
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailException('SMTP delivery failed: ' . $mail->ErrorInfo, 0, $e);
        }
    }
}
