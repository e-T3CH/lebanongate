<?php

declare(strict_types=1);

namespace Gate\Mail;

use Gate\I18n\Translator;

/**
 * Emails of the admin panel itself: an invitation to a new colleague, the confirmation of an own email change and
 * the optional status update to the customer. The links carry a one-time token and are never written to the log.
 */
final class AdminMails
{
    public static function invitation(string $to, string $name, string $siteName, string $link, string $invitedBy, int $hours, Translator $t): MailMessage
    {
        $subject = $t->get('admin.mail.invite_subject', ['site' => $siteName]);
        $body = $t->get('admin.mail.invite_greeting', ['name' => $name]) . "\n\n"
            . $t->get('admin.mail.invite_body', ['site' => $siteName, 'by' => $invitedBy, 'hours' => $hours]) . "\n\n"
            . $link . "\n\n"
            . $t->get('admin.mail.invite_ignore') . "\n";
        return new MailMessage($to, $name, self::oneLine($subject), $body, self::html($body, $t->get('admin.mail.invite_button'), $link));
    }

    public static function emailChange(string $to, string $name, string $siteName, string $link, int $hours, Translator $t): MailMessage
    {
        $subject = $t->get('admin.mail.email_change_subject', ['site' => $siteName]);
        $body = $t->get('admin.mail.invite_greeting', ['name' => $name]) . "\n\n"
            . $t->get('admin.mail.email_change_body', ['site' => $siteName, 'hours' => $hours]) . "\n\n"
            . $link . "\n\n"
            . $t->get('admin.mail.email_change_ignore') . "\n";
        return new MailMessage($to, $name, self::oneLine($subject), $body, self::html($body, $t->get('admin.mail.email_change_button'), $link));
    }

    public static function passwordReset(string $to, string $name, string $siteName, string $link, int $minutes, Translator $t): MailMessage
    {
        $subject = $t->get('admin.mail.reset_subject', ['site' => $siteName]);
        $body = $t->get('admin.mail.invite_greeting', ['name' => $name]) . "

"
            . $t->get('admin.mail.reset_body', ['site' => $siteName, 'minutes' => $minutes]) . "

"
            . $link . "

"
            . $t->get('admin.mail.reset_ignore') . "
";
        return new MailMessage($to, $name, self::oneLine($subject), $body, self::html($body, $t->get('admin.mail.reset_button'), $link));
    }

    /**
     * Status update to the customer, from the template of that status in their language.
     * Placeholders: :name, :car, :status, :site, :phone.
     *
     * @param array<string, string> $values
     */
    public static function statusUpdate(string $to, string $name, string $subject, string $body, array $values, string $siteName, string $replyTo): MailMessage
    {
        $replace = static function (string $text) use ($values): string {
            foreach ($values as $key => $value) {
                $text = str_replace(':' . $key, $value, $text);
            }
            return $text;
        };
        $text = $replace($body);
        return new MailMessage($to, $name, self::oneLine($replace($subject)), $text, self::html($text, '', ''), filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false ? $replyTo : '');
    }

    private static function html(string $text, string $buttonLabel, string $link): string
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $paragraphs = '';
        foreach (preg_split('/\n{2,}/', trim($text)) ?: [] as $block) {
            if ($link !== '' && trim($block) === $link) {
                continue;
            }
            $paragraphs .= '<p style="margin:0 0 16px">' . nl2br($e($block), false) . '</p>';
        }
        $button = $link !== '' && $buttonLabel !== ''
            ? '<p style="margin:24px 0"><a href="' . $e($link) . '" style="display:inline-block;padding:12px 22px;border-radius:10px;background:#0E6BA6;color:#FFFFFF;font-weight:600;text-decoration:none">' . $e($buttonLabel) . '</a></p>'
                . '<p style="margin:0;color:#5B6F84;font-size:12px;word-break:break-all">' . $e($link) . '</p>'
            : '';
        return '<!doctype html><html><body style="margin:0;padding:24px;background:#F4F7FB;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#0B1A2B">'
            . '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E2E9F0;border-radius:12px;padding:24px">'
            . $paragraphs . $button . '</div></body></html>';
    }

    private static function oneLine(string $s): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', $s)), 0, 200);
    }
}
