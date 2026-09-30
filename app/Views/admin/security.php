<?php
/**
 * Settings → Security (Phase 1 part of the approved admin-security mock-up), built from components.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var bool $twoFactorOn
 * @var int $recoveryCount
 * @var mixed $twoFactorError
 * @var string $currentIp
 * @var bool $isHttps
 * @var string $adminPath
 */
$v = static fn (string $k): string => is_scalar($values[$k] ?? null) ? (string) $values[$k] : '';
$errorBlock = static fn (string $field): string => isset($errors[$field]) ? (string) $view->component('form-error', ['message' => $errors[$field]]) : '';
?>
<div class="flex gap-20 agrow">
  <div class="acard agrow sec-card">
    <div class="sec-card__head">
      <h2 class="h3"><?= e($view->t('admin.security.card_title')) ?></h2>
<?php if ($twoFactorOn): ?>
      <?= $view->component('status-pill', ['label' => $view->t('admin.security.two_factor_on'), 'tone' => 'confirmed', 'dot' => true]) ?>

<?php else: ?>
      <?= $view->component('status-pill', ['label' => $view->t('admin.security.two_factor_recommended'), 'tone' => 'diagnosis', 'icon' => 'fa-solid fa-shield-halved']) ?>

<?php endif; ?>
    </div>

    <?= $view->component('setting-row', [
        'name' => $view->t('admin.security.two_factor'),
        'description' => $view->t($twoFactorOn ? 'admin.security.two_factor_on_desc' : 'admin.security.two_factor_off_desc', ['count' => $recoveryCount]),
        'wide' => true,
        'control' => $view->component('toggle', ['label' => $view->t('admin.security.two_factor'), 'checked' => $twoFactorOn, 'disabled' => true, 'attrs' => ['tabindex' => '-1', 'aria-hidden' => 'true']]),
    ]) ?>

<?php if (is_string($twoFactorError)): ?>
    <?= $view->component('notice', ['title' => $twoFactorError, 'role' => 'alert', 'class' => 'sec-card__alert']) ?>

<?php endif; ?>
<?php if (!$twoFactorOn): ?>
    <form class="fields-2 fields-2--sec" method="post" action="<?= e_url($adminPath . '/security/two-factor/setup') ?>">
      <?= $view->csrfField() ?>
      <?= $view->component('input', ['name' => 'password', 'id' => 'tfa-setup-password', 'type' => 'password', 'label' => $view->t('admin.security.confirm_password'), 'autocomplete' => 'current-password', 'required' => true]) ?>
      <div class="actions actions--end"><?= $view->component('button', ['label' => $view->t('admin.security.enable_two_factor'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-qrcode', 'iconPosition' => 'start']) ?></div>
    </form>
<?php else: ?>
    <form class="fields-3 fields-3--sec" method="post" action="<?= e_url($adminPath . '/security/two-factor/disable') ?>" data-confirm="<?= e_attr($view->t('admin.security.disable_two_factor_confirm')) ?>" data-confirm-title="<?= e_attr($view->t('admin.security.disable_two_factor')) ?>" data-confirm-ok="<?= e_attr($view->t('admin.security.disable_two_factor')) ?>" data-confirm-tone="danger">
      <?= $view->csrfField() ?>
      <?= $view->component('input', ['name' => 'password', 'id' => 'tfa-disable-password', 'type' => 'password', 'label' => $view->t('admin.security.confirm_password'), 'autocomplete' => 'current-password', 'required' => true]) ?>
      <?= $view->component('input', ['name' => 'code', 'id' => 'tfa-disable-code', 'label' => $view->t('admin.security.current_code'), 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric', 'required' => true]) ?>
      <div class="actions actions--end"><?= $view->component('button', ['label' => $view->t('admin.security.disable_two_factor'), 'variant' => 'admin-secondary', 'type' => 'submit']) ?></div>
    </form>
    <form class="fields-3 fields-3--sec" method="post" action="<?= e_url($adminPath . '/security/two-factor/recovery-codes') ?>">
      <?= $view->csrfField() ?>
      <?= $view->component('input', ['name' => 'password', 'id' => 'tfa-codes-password', 'type' => 'password', 'label' => $view->t('admin.security.confirm_password'), 'autocomplete' => 'current-password', 'required' => true]) ?>
      <?= $view->component('input', ['name' => 'code', 'id' => 'tfa-codes-code', 'label' => $view->t('admin.security.current_code'), 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric', 'required' => true]) ?>
      <div class="actions actions--end"><?= $view->component('button', ['label' => $view->t('admin.security.new_recovery_codes'), 'variant' => 'admin-secondary', 'type' => 'submit']) ?></div>
    </form>
<?php endif; ?>

    <form method="post" action="<?= e_url($adminPath . '/security') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="row">
        <span class="setting setting--wide"><span class="setting__name"><?= e($view->t('admin.security.require_two_factor')) ?></span><span class="muted"><?= e($view->t('admin.security.require_two_factor_desc')) ?></span><?= $errorBlock('two_factor_required') ?></span>
        <?= $view->component('toggle', ['name' => 'two_factor_required', 'label' => $view->t('admin.security.require_two_factor'), 'checked' => !empty($values['two_factor_required']), 'disabled' => !$twoFactorOn]) ?>
      </div>
      <div class="row">
        <span class="setting setting--wide"><span class="setting__name"><?= e($view->t('admin.security.force_https')) ?></span><span class="muted"><?= e($view->t('admin.security.force_https_desc')) ?></span><?= $errorBlock('force_https') ?></span>
        <?= $view->component('toggle', ['name' => 'force_https', 'label' => $view->t('admin.security.force_https'), 'checked' => !empty($values['force_https'])]) ?>
      </div>
      <div class="fields-3 fields-3--sec">
        <?= $view->component('input', ['name' => 'max_failed_logins', 'label' => $view->t('admin.security.max_failed_logins'), 'value' => $v('max_failed_logins'), 'inputmode' => 'numeric', 'error' => $errors['max_failed_logins'] ?? null]) ?>
        <?= $view->component('input', ['name' => 'lockout_minutes', 'label' => $view->t('admin.security.lockout_minutes'), 'value' => $v('lockout_minutes'), 'inputmode' => 'numeric', 'error' => $errors['lockout_minutes'] ?? null]) ?>
        <?= $view->component('input', ['name' => 'session_timeout', 'label' => $view->t('admin.security.session_timeout'), 'value' => $v('session_timeout'), 'inputmode' => 'numeric', 'error' => $errors['session_timeout'] ?? null]) ?>
      </div>
      <div class="fields-2 fields-2--sec">
        <?= $view->component('input', ['name' => 'ip_max_failed_logins', 'label' => $view->t('admin.security.ip_max_failed_logins'), 'value' => $v('ip_max_failed_logins'), 'inputmode' => 'numeric', 'error' => $errors['ip_max_failed_logins'] ?? null]) ?>
        <?= $view->component('input', ['name' => 'admin_path', 'label' => $view->t('admin.security.admin_path'), 'value' => $v('admin_path'), 'error' => $errors['admin_path'] ?? null, 'hint' => $view->t('admin.security.admin_path_hint')]) ?>
      </div>
      <div class="fields-2--sec">
        <?= $view->component('textarea', ['name' => 'admin_ip_allowlist', 'label' => $view->t('admin.security.ip_allowlist'), 'value' => $v('admin_ip_allowlist'), 'placeholder' => $view->t('admin.security.ip_allowlist_placeholder'), 'error' => $errors['admin_ip_allowlist'] ?? null, 'hint' => $view->t('admin.security.ip_allowlist_hint', ['ip' => $currentIp])]) ?>
      </div>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/security']) ?><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
</div>
