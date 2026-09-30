<?php

declare(strict_types=1);

namespace Gate\Mail;

use Gate\I18n\Translator;

/**
 * The two emails for an appointment request: a notification to the business (admin panel language, reply-to the
 * customer) and a confirmation to the customer in the language of the page they used.
 */
final class AppointmentMails
{
    /**
     * @param array{name: string, phone: string, email: string, car: string, gearbox_type: string, symptoms: string} $values
     */
    public static function business(array $values, int $id, string $to, string $siteName, string $adminUrl, Translator $t): MailMessage
    {
        $type = $t->get('site.form.types.' . $values['gearbox_type']);
        $rows = [
            $t->get('site.form.name') => $values['name'],
            $t->get('site.form.phone') => $values['phone'],
            $t->get('site.form.email') => $values['email'],
            $t->get('site.form.car') => $values['car'],
            $t->get('site.form.type') => $type,
            $t->get('site.form.symptoms') => $values['symptoms'],
        ];
        $subject = $t->get('site.mail.business_subject', ['id' => $id, 'name' => $values['name'], 'car' => $values['car']]);
        $intro = $t->get('site.mail.business_intro', ['site' => $siteName]);
        $text = $intro . "\n\n";
        foreach ($rows as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
        }
        $text .= "\n" . $t->get('site.mail.business_admin', ['url' => $adminUrl]) . "\n";
        return new MailMessage($to, $siteName, self::oneLine($subject), $text, self::html($intro, $rows, $t->get('site.mail.business_admin', ['url' => $adminUrl])), $values['email']);
    }

    /**
     * @param array{name: string, phone: string, email: string, car: string, gearbox_type: string, symptoms: string} $values
     */
    public static function customer(array $values, string $siteName, string $phone, string $replyTo, Translator $t): MailMessage
    {
        $subject = $t->get('site.mail.customer_subject', ['site' => $siteName]);
        $greeting = $t->get('site.mail.customer_greeting', ['name' => $values['name']]);
        $body = $t->get('site.mail.customer_body', ['phone' => $phone]);
        $rows = [
            $t->get('site.form.car') => $values['car'],
            $t->get('site.form.type') => $t->get('site.form.types.' . $values['gearbox_type']),
            $t->get('site.form.symptoms') => $values['symptoms'],
        ];
        $signature = $t->get('site.mail.customer_signature', ['site' => $siteName]);
        $text = $greeting . "\n\n" . $body . "\n\n";
        foreach ($rows as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
        }
        $text .= "\n" . $signature . "\n";
        $html = self::html($greeting . "\n\n" . $body, $rows, $signature);
        return new MailMessage($values['email'], $values['name'], self::oneLine($subject), $text, $html, filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false ? $replyTo : '');
    }

    /** @param array<string, string> $rows */
    private static function html(string $intro, array $rows, string $footer): string
    {
        $e = static fn (string $s): string => nl2br(htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr><th align="left" valign="top" style="padding:6px 16px 6px 0;color:#5B6F84;font-weight:600">' . $e($label) . '</th><td style="padding:6px 0;color:#0B1A2B">' . $e($value) . '</td></tr>';
        }
        return '<!doctype html><html><body style="margin:0;padding:24px;background:#F4F7FB;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#0B1A2B">'
            . '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E2E9F0;border-radius:12px;padding:24px">'
            . '<p style="margin:0 0 16px">' . $e($intro) . '</p><table cellpadding="0" cellspacing="0">' . $table . '</table>'
            . '<p style="margin:16px 0 0;color:#5B6F84">' . $e($footer) . '</p></div></body></html>';
    }

    private static function oneLine(string $s): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', $s)), 0, 200);
    }
}
