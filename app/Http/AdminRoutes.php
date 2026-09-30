<?php

declare(strict_types=1);

namespace Gate\Http;

use Gate\Core\App;
use Gate\Http\Controllers\Admin\AppearanceController;
use Gate\Http\Controllers\Admin\AppointmentController;
use Gate\Http\Controllers\Admin\AuthController;
use Gate\Http\Controllers\Admin\ContentListController;
use Gate\Http\Controllers\Admin\DashboardController;
use Gate\Http\Controllers\Admin\EmailSettingsController;
use Gate\Http\Controllers\Admin\MaintenanceController;
use Gate\Http\Controllers\Admin\MediaController;
use Gate\Http\Controllers\Admin\MessageController;
use Gate\Http\Controllers\Admin\PageController;
use Gate\Http\Controllers\Admin\PasswordResetController;
use Gate\Http\Controllers\Admin\ProfileController;
use Gate\Http\Controllers\Admin\ReviewController;
use Gate\Http\Controllers\Admin\SecurityController;
use Gate\Http\Controllers\Admin\SecurityLogController;
use Gate\Http\Controllers\Admin\ServiceController;
use Gate\Http\Controllers\Admin\SettingsController;
use Gate\Http\Controllers\Admin\TwoFactorChallengeController;
use Gate\Http\Controllers\Admin\UserController;

/**
 * Every admin route in one place, with the permission it needs.
 *
 * Flags: `auth` (signed in), `2fa-exempt` (reachable while 2FA setup is still required) and `perm:<name>` —
 * checked by App against config/permissions.php before the controller runs. Views ask the same question with
 * $view->can('<name>'), so a screen never shows an action the request would refuse.
 */
final class AdminRoutes
{
    public static function register(App $app, Router $router, string $prefix): void
    {
        $auth = new AuthController($app);
        $challenge = new TwoFactorChallengeController($app);
        $dashboard = new DashboardController($app);
        $security = new SecurityController($app);
        $email = new EmailSettingsController($app);
        $users = new UserController($app);
        $profile = new ProfileController($app);
        $appointments = new AppointmentController($app);
        $messages = new MessageController($app);
        $pages = new PageController($app);
        $services = new ServiceController($app);
        $lists = new ContentListController($app);
        $media = new MediaController($app);
        $settings = new SettingsController($app);
        $appearance = new AppearanceController($app);
        $log = new SecurityLogController($app);
        $reviews = new ReviewController($app);
        $resets = new PasswordResetController($app);
        $maintenance = new MaintenanceController($app, $settings);

        // Sign in, two-factor challenge, forgotten password and the invitation link (no session yet).
        $router->get($prefix . '/login', $auth->showLogin(...), 'admin.login');
        $router->post($prefix . '/login', $auth->login(...), 'admin.login.submit');
        $router->post($prefix . '/logout', $auth->logout(...), 'admin.logout', ['auth']);
        $router->get($prefix . '/two-factor', $challenge->show(...), 'admin.2fa.challenge');
        $router->post($prefix . '/two-factor', $challenge->verify(...), 'admin.2fa.verify');
        $router->post($prefix . '/two-factor/cancel', $challenge->cancel(...), 'admin.2fa.cancel');
        $router->get($prefix . '/forgot-password', $resets->showRequest(...), 'admin.password.forgot');
        $router->post($prefix . '/forgot-password', $resets->sendLink(...), 'admin.password.email');
        $router->get($prefix . '/reset-password/{token}', $resets->showReset(...), 'admin.password.reset');
        $router->post($prefix . '/reset-password/{token}', $resets->reset(...), 'admin.password.update');
        $router->get($prefix . '/invitation/{token}', $users->showInvitation(...), 'admin.invitation');
        $router->post($prefix . '/invitation/{token}', $users->acceptInvitation(...), 'admin.invitation.accept');
        $router->get($prefix . '/email-change/{token}', $profile->confirmEmail(...), 'admin.profile.email.confirm', ['auth']);

        // Dashboard
        $router->get($prefix, $dashboard->index(...), 'admin.dashboard', ['auth', 'perm:dashboard.view']);
        $router->post($prefix . '/quick-toggle', $dashboard->quickToggle(...), 'admin.dashboard.toggle', ['auth', 'perm:settings.manage']);

        // Appointments (workflow) and Messages (inbox): the same records, two views.
        $router->get($prefix . '/appointments', $appointments->index(...), 'admin.appointments', ['auth', 'perm:appointments.view']);
        $router->get($prefix . '/appointments/export', $appointments->export(...), 'admin.appointments.export', ['auth', 'perm:appointments.view']);
        $router->get($prefix . '/appointments/{id}', $appointments->show(...), 'admin.appointments.show', ['auth', 'perm:appointments.view']);
        $router->post($prefix . '/appointments/{id}/status', $appointments->changeStatus(...), 'admin.appointments.status', ['auth', 'perm:appointments.manage']);
        $router->post($prefix . '/appointments/{id}/notes', $appointments->addNote(...), 'admin.appointments.notes', ['auth', 'perm:appointments.manage']);
        $router->post($prefix . '/appointments/{id}/notes/{note}/delete', $appointments->deleteNote(...), 'admin.appointments.notes.delete', ['auth', 'perm:appointments.manage']);
        $router->post($prefix . '/appointments/{id}/unread', $appointments->markUnread(...), 'admin.appointments.unread', ['auth', 'perm:appointments.manage']);
        $router->get($prefix . '/messages', $messages->index(...), 'admin.messages', ['auth', 'perm:appointments.view']);

        // Content: pages with their sections, services and the short lists
        $router->get($prefix . '/pages', $pages->index(...), 'admin.pages', ['auth', 'perm:content.view']);
        $router->get($prefix . '/pages/{id}', $pages->edit(...), 'admin.pages.edit', ['auth', 'perm:content.view']);
        $router->post($prefix . '/pages/{id}', $pages->save(...), 'admin.pages.save', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/pages/{id}/sections', $pages->saveSections(...), 'admin.pages.sections', ['auth', 'perm:content.edit']);
        $router->get($prefix . '/pages/{id}/sections/{section}', $pages->editSection(...), 'admin.pages.section', ['auth', 'perm:content.view']);
        $router->post($prefix . '/pages/{id}/sections/{section}', $pages->saveSection(...), 'admin.pages.section.save', ['auth', 'perm:content.edit']);
        $router->get($prefix . '/services', $services->index(...), 'admin.services', ['auth', 'perm:content.view']);
        $router->post($prefix . '/services/new', $services->create(...), 'admin.services.create', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/services/order', $services->reorder(...), 'admin.services.order', ['auth', 'perm:content.edit']);
        $router->get($prefix . '/services/{id}', $services->edit(...), 'admin.services.edit', ['auth', 'perm:content.view']);
        $router->post($prefix . '/services/{id}', $services->save(...), 'admin.services.save', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/services/{id}/delete', $services->delete(...), 'admin.services.delete', ['auth', 'perm:content.edit']);
        $router->get($prefix . '/content/{type}', $lists->index(...), 'admin.content.list', ['auth', 'perm:content.view']);
        $router->post($prefix . '/content/{type}', $lists->save(...), 'admin.content.list.save', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/content/partners/partners', $lists->savePartners(...), 'admin.content.partners.save', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/content/{type}/new', $lists->add(...), 'admin.content.list.add', ['auth', 'perm:content.edit']);
        $router->post($prefix . '/content/{type}/{id}/delete', $lists->delete(...), 'admin.content.list.delete', ['auth', 'perm:content.edit']);

        // Google reviews
        $router->get($prefix . '/reviews', $reviews->index(...), 'admin.reviews', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/connection', $reviews->saveConnection(...), 'admin.reviews.connection', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/display', $reviews->saveDisplay(...), 'admin.reviews.display', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/sync', $reviews->sync(...), 'admin.reviews.sync', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/bulk', $reviews->bulk(...), 'admin.reviews.bulk', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/import', $reviews->importPreview(...), 'admin.reviews.import', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/import/confirm', $reviews->importConfirm(...), 'admin.reviews.import.confirm', ['auth', 'perm:reviews.manage']);
        $router->get($prefix . '/reviews/connect', $reviews->connect(...), 'admin.reviews.connect', ['auth', 'perm:reviews.manage']);
        $router->get($prefix . '/reviews/callback', $reviews->callback(...), 'admin.reviews.callback', ['auth', 'perm:reviews.manage']);
        $router->post($prefix . '/reviews/{id}/visibility', $reviews->setVisibility(...), 'admin.reviews.visibility', ['auth', 'perm:reviews.manage']);

        // Media library
        $router->get($prefix . '/media', $media->index(...), 'admin.media', ['auth', 'perm:media.view']);
        $router->post($prefix . '/media/upload', $media->upload(...), 'admin.media.upload', ['auth', 'perm:media.manage']);
        $router->post($prefix . '/media/{id}/replace', $media->replace(...), 'admin.media.replace', ['auth', 'perm:media.manage']);
        $router->post($prefix . '/media/{id}/alt', $media->saveAlt(...), 'admin.media.alt', ['auth', 'perm:media.manage']);
        $router->post($prefix . '/media/{id}/delete', $media->delete(...), 'admin.media.delete', ['auth', 'perm:media.manage']);

        // Users and own profile
        $router->get($prefix . '/users', $users->index(...), 'admin.users', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/invite', $users->invite(...), 'admin.users.invite', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/invitations/{id}/resend', $users->resendInvitation(...), 'admin.users.invite.resend', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/invitations/{id}/cancel', $users->cancelInvitation(...), 'admin.users.invite.cancel', ['auth', 'perm:users.manage']);
        $router->get($prefix . '/users/{id}', $users->edit(...), 'admin.users.edit', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/{id}', $users->save(...), 'admin.users.save', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/{id}/active', $users->setActive(...), 'admin.users.active', ['auth', 'perm:users.manage']);
        $router->post($prefix . '/users/{id}/two-factor-reset', $users->resetTwoFactor(...), 'admin.users.2fa.reset', ['auth', 'perm:users.manage']);
        $router->get($prefix . '/profile', $profile->index(...), 'admin.profile', ['auth']);
        $router->post($prefix . '/profile', $profile->save(...), 'admin.profile.save', ['auth']);
        $router->post($prefix . '/profile/password', $profile->changePassword(...), 'admin.profile.password', ['auth']);

        // Security, email and the rest of the settings
        $router->get($prefix . '/security', $security->index(...), 'admin.security', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/security', $security->save(...), 'admin.security.save', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/security/two-factor/setup', $security->startSetup(...), 'admin.2fa.setup', ['auth', '2fa-exempt']);
        $router->get($prefix . '/security/two-factor/setup', $security->showSetup(...), 'admin.2fa.setup.show', ['auth', '2fa-exempt']);
        $router->post($prefix . '/security/two-factor/confirm', $security->confirmSetup(...), 'admin.2fa.confirm', ['auth', '2fa-exempt']);
        $router->get($prefix . '/security/two-factor/recovery-codes', $security->showRecoveryCodes(...), 'admin.2fa.codes', ['auth', '2fa-exempt']);
        $router->post($prefix . '/security/two-factor/recovery-codes', $security->regenerateRecoveryCodes(...), 'admin.2fa.codes.regenerate', ['auth']);
        $router->post($prefix . '/security/two-factor/disable', $security->disable(...), 'admin.2fa.disable', ['auth']);
        $router->get($prefix . '/security/log', $log->index(...), 'admin.security.log', ['auth', 'perm:security.manage']);
        $router->get($prefix . '/security/log/export', $log->export(...), 'admin.security.log.export', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/security/log/retention', $log->saveRetention(...), 'admin.security.log.retention', ['auth', 'perm:security.manage']);
        $router->get($prefix . '/appearance', $appearance->index(...), 'admin.appearance', ['auth', 'perm:appearance.manage']);
        $router->post($prefix . '/appearance', $appearance->save(...), 'admin.appearance.save', ['auth', 'perm:appearance.manage']);
        $router->post($prefix . '/appearance/reset', $appearance->reset(...), 'admin.appearance.reset', ['auth', 'perm:appearance.manage']);
        $router->get($prefix . '/settings/general', $settings->general(...), 'admin.settings.general', ['auth', 'perm:settings.manage']);
        $router->post($prefix . '/settings/general', $settings->saveGeneral(...), 'admin.settings.general.save', ['auth', 'perm:settings.manage']);
        $router->get($prefix . '/settings/languages', $settings->languages(...), 'admin.languages', ['auth', 'perm:languages.manage']);
        $router->post($prefix . '/settings/languages', $settings->saveLanguages(...), 'admin.languages.save', ['auth', 'perm:languages.manage']);
        $router->get($prefix . '/settings/social', $settings->social(...), 'admin.settings.social', ['auth', 'perm:settings.manage']);
        $router->post($prefix . '/settings/social', $settings->saveSocial(...), 'admin.settings.social.save', ['auth', 'perm:settings.manage']);
        $router->get($prefix . '/settings', static fn (): Response => Response::redirect($prefix . '/settings/general'), 'admin.settings', ['auth', 'perm:settings.manage']);
        $router->get($prefix . '/settings/email', $email->index(...), 'admin.settings.email', ['auth', 'perm:settings.manage']);
        $router->post($prefix . '/settings/email', $email->save(...), 'admin.settings.email.save', ['auth', 'perm:settings.manage']);
        $router->post($prefix . '/settings/email/test', $email->test(...), 'admin.settings.email.test', ['auth', 'perm:settings.manage']);
        $router->get($prefix . '/settings/maintenance', $maintenance->index(...), 'admin.maintenance', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/scheduler/run', $maintenance->runScheduler(...), 'admin.maintenance.scheduler.run', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/scheduler/regenerate', $maintenance->regenerateScheduler(...), 'admin.maintenance.scheduler.regenerate', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/backups', $maintenance->makeBackup(...), 'admin.maintenance.backup', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/backups/upload', $maintenance->upload(...), 'admin.maintenance.backup.upload', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/backups/restore', $maintenance->restore(...), 'admin.maintenance.backup.restore', ['auth', 'perm:security.manage']);
        $router->get($prefix . '/settings/maintenance/backups/{file}', $maintenance->download(...), 'admin.maintenance.backup.download', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/maintenance/analytics', $maintenance->saveAnalytics(...), 'admin.maintenance.analytics', ['auth', 'perm:security.manage']);
        $router->post($prefix . '/settings/email/customers', $email->saveCustomerEmails(...), 'admin.settings.email.customers', ['auth', 'perm:settings.manage']);
    }
}
