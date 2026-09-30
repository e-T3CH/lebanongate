<?php
/**
 * Settings → Email: SMTP settings, recipient of appointment requests, queue status, a test button, and the emails to
 * customers when a request changes status (one switch per status, the text per language; everything off by default).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var bool $hasPassword
 * @var array{pending: int, sent: int, failed: int} $queue
 * @var string $testTo
 * @var array{lang: string, tabs: list<array{label: string, href: string, active: bool}>, rows: list<array{status: string, label: string, enabled: bool, subject: string, body: string}>} $customer
 * @var string $adminPath
 */
$v = static fn (string $k): string => is_scalar($values[$k] ?? null) ? (string) $values[$k] : '';
$encryptions = array_map(static fn (string $e): array => ['value' => $e, 'label' => $view->t('admin.email.encryption_' . $e)], ['tls', 'ssl', 'none']);
$testError = $view->shared('app') instanceof \Gate\Core\App ? $view->shared('app')->session()->pull('email_test_error') : null;
?>
<?= $view->component('tabs', ['label' => $view->t('admin.email.tabs_label'), 'items' => [
    ['label' => $view->t('admin.nav.security'), 'href' => $adminPath . '/security'],
    ['label' => $view->t('admin.email.tab'), 'href' => $adminPath . '/settings/email', 'active' => true],
]]) ?>

<div class="flex gap-20 agrow">
  <form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/settings/email') ?>" novalidate>
    <?= $view->csrfField() ?>
    <div class="sec-card__head"><h2 class="h3"><?= e($view->t('admin.email.card_title')) ?></h2><?= $view->component('status-pill', ['label' => $view->t($v('host') !== '' ? 'admin.email.configured' : 'admin.email.not_configured'), 'tone' => $v('host') !== '' ? 'confirmed' : 'diagnosis', 'dot' => $v('host') !== '']) ?></div>
    <?= $view->component('setting-row', ['name' => $view->t('admin.email.enabled'), 'description' => $view->t('admin.email.enabled_desc'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'enabled', 'label' => $view->t('admin.email.enabled'), 'checked' => ($values['enabled'] ?? false) === true])]) ?>

    <div class="fields-3 fields-3--sec">
      <?= $view->component('input', ['name' => 'host', 'label' => $view->t('admin.email.host'), 'value' => $v('host'), 'placeholder' => 'smtp.example.com', 'error' => $errors['host'] ?? null]) ?>
      <?= $view->component('input', ['name' => 'port', 'label' => $view->t('admin.email.port'), 'value' => $v('port'), 'inputmode' => 'numeric', 'error' => $errors['port'] ?? null]) ?>
      <?= $view->component('select', ['name' => 'encryption', 'id' => 'mail-encryption', 'label' => $view->t('admin.email.encryption'), 'options' => $encryptions, 'value' => $v('encryption'), 'error' => $errors['encryption'] ?? null]) ?>
    </div>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'username', 'label' => $view->t('admin.email.username'), 'value' => $v('username'), 'autocomplete' => 'off']) ?>
      <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('admin.email.password'), 'autocomplete' => 'new-password', 'hint' => $view->t($hasPassword ? 'admin.email.password_kept' : 'admin.email.password_empty')]) ?>
      <?= $view->component('input', ['name' => 'from_email', 'type' => 'email', 'label' => $view->t('admin.email.from_email'), 'value' => $v('from_email'), 'error' => $errors['from_email'] ?? null]) ?>
      <?= $view->component('input', ['name' => 'from_name', 'label' => $view->t('admin.email.from_name'), 'value' => $v('from_name'), 'error' => $errors['from_name'] ?? null]) ?>
      <?= $view->component('input', ['name' => 'to_email', 'type' => 'email', 'label' => $view->t('admin.email.to_email'), 'value' => $v('to_email'), 'hint' => $view->t('admin.email.to_email_hint'), 'error' => $errors['to_email'] ?? null]) ?>
    </div>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/settings/email']) ?><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
  </form>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/settings/email/test') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.email.test_title')) ?></h2><span class="muted"><?= e($view->t('admin.email.test_desc')) ?></span></div>
<?php if (is_string($testError)): ?>
      <?= $view->component('notice', ['title' => $view->t('admin.email.test_failed'), 'text' => $testError, 'role' => 'alert']) ?>

<?php endif; ?>
      <?= $view->component('input', ['name' => 'test_to', 'type' => 'email', 'label' => $view->t('admin.email.test_to'), 'value' => $testTo]) ?>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.email.test_button'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-regular fa-paper-plane', 'iconPosition' => 'start']) ?></div>
    </form>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.email.queue_title')) ?></h2><span class="muted"><?= e($view->t('admin.email.queue_desc')) ?></span></div>
      <div class="dc-row flex gap-10"><?= $view->component('status-pill', ['label' => $view->t('admin.email.queue_pending', ['n' => $queue['pending']]), 'tone' => 'new']) ?><?= $view->component('status-pill', ['label' => $view->t('admin.email.queue_sent', ['n' => $queue['sent']]), 'tone' => 'confirmed']) ?><?= $view->component('status-pill', ['label' => $view->t('admin.email.queue_failed', ['n' => $queue['failed']]), 'tone' => $queue['failed'] > 0 ? 'danger' : 'neutral']) ?></div>
    </div>
  </div>
</div>

<form class="acard sec-card" id="customer-emails" method="post" action="<?= e_url($adminPath . '/settings/email/customers') ?>" novalidate>
  <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($customer['lang']) ?>">
  <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.email.customer_title')) ?></h2><span class="muted"><?= e($view->t('admin.email.customer_desc')) ?></span><span class="muted"><?= e($view->t('admin.email.customer_placeholders')) ?></span></div>
  <?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $customer['tabs']]) ?>

<?php foreach ($customer['rows'] as $row): ?>
  <div class="customer-email">
    <?= $view->component('setting-row', ['name' => $row['label'], 'description' => $view->t('admin.email.customer_send'), 'control' => $view->component('toggle', ['name' => 'enabled_' . $row['status'], 'label' => $view->t('admin.email.customer_send') . ' — ' . $row['label'], 'checked' => $row['enabled']])]) ?>

    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'subject_' . $row['status'], 'label' => $view->t('admin.email.customer_subject'), 'value' => $row['subject'], 'maxlength' => 200]) ?>
      <?= $view->component('textarea', ['name' => 'body_' . $row['status'], 'label' => $view->t('admin.email.customer_body'), 'value' => $row['body'], 'rows' => 7, 'maxlength' => 4000]) ?>
    </div>
  </div>
<?php endforeach; ?>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
</form>
