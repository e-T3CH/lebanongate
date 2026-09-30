<?php

declare(strict_types=1);

namespace Gate\Mail;

use Gate\I18n\Translator;

/**
 * Emails of the public forms: the contact notification to GATE Lebanon (admin panel language, reply-to the sender),
 * the confirmation to the sender and the newsletter confirmation link (both in the language of the page they used).
 * Arabic messages are written right to left.
 */
final class ContactMails
{
    /**
     * @param array{name: string, email: string, phone: string, organisation: string, subject: string, message: string} $values
     */
    public static function organisation(array $values, int $id, string $to, string $siteName, string $adminUrl, Translator $t): MailMessage
    {
        $rows = [
            $t->get('site.form.name') => $values['name'],
            $t->get('site.form.email') => $values['email'],
            $t->get('site.form.phone') => $values['phone'],
            $t->get('site.form.organisation') => $values['organisation'],
            $t->get('site.form.subject') => $t->get('site.form.subjects.' . $values['subject']),
            $t->get('site.form.message') => $values['message'],
        ];
        $rows = array_filter($rows, static fn (string $v): bool => $v !== '');
        $subject = $t->get('site.mail.org_subject', ['id' => $id, 'name' => $values['name'], 'subject' => $t->get('site.form.subjects.' . $values['subject'])]);
        $intro = $t->get('site.mail.org_intro', ['site' => $siteName]);
        $footer = $t->get('site.mail.org_admin', ['url' => $adminUrl]);
        return new MailMessage($to, $siteName, self::oneLine($subject), self::text($intro, $rows, $footer), self::html($intro, $rows, $footer, 'ltr'), $values['email']);
    }

    /**
     * @param array{name: string, email: string, phone: string, organisation: string, subject: string, message: string} $values
     */
    public static function sender(array $values, string $siteName, string $replyTo, Translator $t): MailMessage
    {
        $subject = $t->get('site.mail.sender_subject', ['site' => $siteName]);
        $intro = $t->get('site.mail.sender_greeting', ['name' => $values['name']]) . "\n\n" . $t->get('site.mail.sender_body');
        $rows = [$t->get('site.form.message') => $values['message']];
        $footer = $t->get('site.mail.signature', ['site' => $siteName]);
        return new MailMessage($values['email'], $values['name'], self::oneLine($subject), self::text($intro, $rows, $footer), self::html($intro, $rows, $footer, self::dir($t)), filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false ? $replyTo : '');
    }

    public static function newsletterConfirm(string $email, string $confirmUrl, string $siteName, Translator $t): MailMessage
    {
        $subject = $t->get('site.mail.newsletter_subject', ['site' => $siteName]);
        $intro = $t->get('site.mail.newsletter_body', ['site' => $siteName]);
        $footer = $t->get('site.mail.newsletter_ignore') . "\n\n" . $t->get('site.mail.signature', ['site' => $siteName]);
        $text = $intro . "\n\n" . $confirmUrl . "\n\n" . $footer . "\n";
        $e = static fn (string $s): string => nl2br(htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
        $button = '<p style="margin:20px 0"><a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#2F6FA8;color:#FFFFFF;text-decoration:none;font-weight:600;padding:12px 20px;border-radius:8px">' . $e($t->get('site.mail.newsletter_button')) . '</a></p>';
        $html = self::wrap('<p style="margin:0 0 8px">' . $e($intro) . '</p>' . $button . '<p style="margin:16px 0 0;color:#5E7185">' . $e($footer) . '</p>', self::dir($t));
        return new MailMessage($email, '', self::oneLine($subject), $text, $html);
    }

    /** @param array<string, string> $rows */
    private static function text(string $intro, array $rows, string $footer): string
    {
        $text = $intro . "\n\n";
        foreach ($rows as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
        }
        return $text . "\n" . $footer . "\n";
    }

    /** @param array<string, string> $rows */
    private static function html(string $intro, array $rows, string $footer, string $dir): string
    {
        $e = static fn (string $s): string => nl2br(htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
        $align = $dir === 'rtl' ? 'right' : 'left';
        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr><th align="' . $align . '" valign="top" style="padding:6px 16px 6px 0;color:#5E7185;font-weight:600">' . $e($label) . '</th><td style="padding:6px 0;color:#12263A">' . $e($value) . '</td></tr>';
        }
        return self::wrap('<p style="margin:0 0 16px">' . $e($intro) . '</p><table cellpadding="0" cellspacing="0">' . $table . '</table><p style="margin:16px 0 0;color:#5E7185">' . $e($footer) . '</p>', $dir);
    }

    private static function wrap(string $inner, string $dir): string
    {
        return '<!doctype html><html dir="' . $dir . '"><body style="margin:0;padding:24px;background:#F5F8FB;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#12263A">'
            . '<div dir="' . $dir . '" style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #DDE6EF;border-top:4px solid #5091CD;border-radius:12px;padding:24px">'
            . $inner . '</div></body></html>';
    }

    private static function dir(Translator $t): string
    {
        return \Gate\I18n\LanguageRules::direction($t->locale());
    }

    private static function oneLine(string $s): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', $s)), 0, 200);
    }
}
