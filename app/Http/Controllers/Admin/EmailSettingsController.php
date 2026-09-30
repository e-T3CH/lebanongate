<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\I18n\Translator;
use Gate\Mail\MailMessage;
use Gate\Mail\MailQueue;
use Gate\Mail\SmtpTransport;
use Gate\Repositories\AppointmentRepository;
use Gate\Services\AuditLog;
use Gate\Services\StatusEmails;

/**
 * Settings → Email: SMTP server, sender, the address that receives appointment requests, and a test button that
 * sends one message immediately and reports the result. The SMTP password is stored encrypted and never shown.
 */
final class EmailSettingsController extends AdminController
{
    private const ENCRYPTIONS = ['tls', 'ssl', 'none'];

    public function index(Request $request): Response
    {
        $s = $this->app->settings();
        $session = $this->app->session();
        $old = $session->pull('email_old');
        $errors = $session->pull('email_errors');
        $user = $this->app->auth()->user();
        return $this->adminView('admin/settings-email', 'settings', $this->t('admin.email.title'), $this->t('admin.email.subtitle'), [
            'values' => is_array($old) ? $old : [
                'enabled' => $s->bool('mail.enabled', true),
                'host' => $s->string('mail.host'),
                'port' => (string) $s->int('mail.port', 587),
                'encryption' => $s->string('mail.encryption', 'tls'),
                'username' => $s->string('mail.username'),
                'from_email' => $s->string('mail.from_email'),
                'from_name' => $s->string('mail.from_name', 'GATE Lebanon'),
                'to_email' => $s->string('mail.to_email'),
            ],
            'errors' => is_array($errors) ? $errors : [],
            'hasPassword' => $s->string('mail.password') !== '',
            'queue' => (new MailQueue($this->app->db(), $this->app->clock))->counts(),
            'testTo' => $user['email'] ?? '',
            'customer' => $this->customerEmails($request->query('lang')),
        ]);
    }

    /**
     * Emails to customers: a switch per status (off by default) and the text per language.
     *
     * @return array{lang: string, tabs: list<array{label: string, href: string, active: bool}>, rows: list<array{status: string, label: string, enabled: bool, subject: string, body: string}>}
     */
    private function customerEmails(string $requested): array
    {
        $languages = $this->app->languages();
        $lang = in_array($requested, $languages->enabledCodes(), true) ? $requested : $languages->defaultCode();
        $emails = new StatusEmails($this->app->db(), $this->app->settings());
        $tabs = [];
        foreach ($languages->enabledCodes() as $code) {
            $tabs[] = ['label' => strtoupper($code), 'href' => $this->app->adminPath('settings/email') . '?lang=' . $code . '#customer-emails', 'active' => $code === $lang];
        }
        $rows = [];
        foreach (AppointmentRepository::STATUSES as $status) {
            $template = $emails->template($status, $lang);
            $rows[] = [
                'status' => $status,
                'label' => $this->t('admin.statuses.' . $status),
                'enabled' => $emails->isEnabled($status),
                'subject' => $template['subject'] ?? '',
                'body' => $template['body'] ?? '',
            ];
        }
        return ['lang' => $lang, 'tabs' => $tabs, 'rows' => $rows];
    }

    /** Saves the switches and the texts of one language. */
    public function saveCustomerEmails(Request $request): Response
    {
        $languages = $this->app->languages();
        $lang = in_array($request->input('lang'), $languages->enabledCodes(), true) ? $request->input('lang') : $languages->defaultCode();
        $emails = new StatusEmails($this->app->db(), $this->app->settings());
        $missing = [];
        foreach (AppointmentRepository::STATUSES as $status) {
            $emails->setEnabled($status, $request->input('enabled_' . $status) === '1');
            $emails->save($status, $lang, $request->input('subject_' . $status), str_replace("\r\n", "\n", $request->input('body_' . $status)));
        }
        // A status that is on but has no text in the default language would never send anything (it is the fallback).
        foreach ($emails->enabledStatuses() as $status) {
            $template = $emails->template($status, $languages->defaultCode());
            if ($template === null || trim($template['subject']) === '' || trim($template['body']) === '') {
                $missing[] = $this->t('admin.statuses.' . $status);
            }
        }
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $this->app->auth()->user()['id'] ?? null, ['keys' => ['appointments.status_email.*'], 'lang' => $lang, 'enabled' => $emails->enabledStatuses()]);
        $missing === []
            ? $this->flashToast('admin.email.customer_saved')
            : $this->flashToast('admin.email.customer_missing', 'error', ['statuses' => implode(', ', $missing), 'lang' => strtoupper($languages->defaultCode())]);
        return $this->back($this->app->adminPath('settings/email') . '?lang=' . $lang . '#customer-emails');
    }

    public function save(Request $request): Response
    {
        $values = [
            'enabled' => $request->input('enabled') === '1',
            'host' => trim($request->input('host')),
            'port' => trim($request->input('port')),
            'encryption' => $request->input('encryption'),
            'username' => trim($request->input('username')),
            'from_email' => trim($request->input('from_email')),
            'from_name' => trim($request->input('from_name')),
            'to_email' => trim($request->input('to_email')),
        ];
        $errors = [];
        if ($values['host'] !== '' && preg_match('/^[A-Za-z0-9.-]{1,253}$/', $values['host']) !== 1) {
            $errors['host'] = $this->t('validation.host');
        }
        if (preg_match('/^\d{1,5}$/', $values['port']) !== 1 || (int) $values['port'] < 1 || (int) $values['port'] > 65535) {
            $errors['port'] = $this->t('validation.between', ['min' => 1, 'max' => 65535]);
        }
        if (!in_array($values['encryption'], self::ENCRYPTIONS, true)) {
            $errors['encryption'] = $this->t('validation.choice');
        }
        foreach (['from_email', 'to_email'] as $field) {
            if ($values[$field] !== '' && filter_var($values[$field], FILTER_VALIDATE_EMAIL) === false) {
                $errors[$field] = $this->t('validation.email');
            }
        }
        if (mb_strlen($values['from_name']) > 100 || preg_match('/[\r\n]/', $values['from_name'] . $values['username']) === 1) {
            $errors['from_name'] = $this->t('validation.max_length', ['max' => 100]);
        }
        if ($errors !== []) {
            $this->app->session()->flash('email_errors', $errors);
            $this->app->session()->flash('email_old', $values);
            return $this->back($this->app->adminPath('settings/email'));
        }
        $s = $this->app->settings();
        $s->set('mail.enabled', $values['enabled']);
        $s->set('mail.host', $values['host']);
        $s->set('mail.port', (int) $values['port']);
        $s->set('mail.encryption', $values['encryption']);
        $s->set('mail.username', $values['username']);
        $s->set('mail.from_email', $values['from_email']);
        $s->set('mail.from_name', $values['from_name'] !== '' ? $values['from_name'] : 'GATE Lebanon');
        $s->set('mail.to_email', $values['to_email']);
        $password = $request->input('password');
        if ($request->input('clear_password') === '1') {
            $s->set('mail.password', '', 'string');
        } elseif ($password !== '') {
            $s->set('mail.password', $password, 'string', true);
        }
        $user = $this->app->auth()->user();
        $this->app->audit()->record(AuditLog::SETTINGS_CHANGED, $user['id'] ?? null, ['keys' => ['mail.*']]);
        $this->flashToast('admin.toast.saved');
        return $this->back($this->app->adminPath('settings/email'));
    }

    public function test(Request $request): Response
    {
        $to = trim($request->input('test_to'));
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->flashToast('validation.email', 'error');
            return $this->back($this->app->adminPath('settings/email'));
        }
        $s = $this->app->settings();
        $site = $s->string('site.name', 'GATE Lebanon');
        $lang = $s->string('admin.language', 'en');
        $t = new Translator($lang, 'en', $this->app->db());
        try {
            SmtpTransport::fromSettings($s)->send(new MailMessage($to, '', $t->get('site.mail.test_subject', ['site' => $site]), $t->get('site.mail.test_body', ['site' => $site])));
            $this->flashToast('admin.email.test_sent', 'success', ['to' => $to]);
        } catch (\Throwable $e) {
            $this->app->session()->flash('email_test_error', mb_substr($e->getMessage(), 0, 300));
            $this->flashToast('admin.email.test_failed', 'error');
        }
        return $this->back($this->app->adminPath('settings/email'));
    }
}
