<?php
/**
 * Settings → Maintenance: the scheduler address, backups (make, download, upload, restore), the uptime-monitor
 * address, the before-going-live content check and analytics. Everything the command line does, for hosts without
 * SSH. Built from the admin components of the approved settings screens (cards, setting rows, data table).
 *
 * @var \Gate\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var string $schedulerUrl
 * @var string|null $lastRun
 * @var array<string, mixed>|null $lastReport
 * @var list<array{name: string, size: int, at: string}> $backups
 * @var string|null $backupError
 * @var string $lastBackupError
 * @var string|null $restoreError
 * @var string|null $uploadError
 * @var int $uploadLimit
 * @var string|null $healthUrl
 * @var array<string, list<string>> $findings
 * @var array{provider: string, ga4_id: string, plausible_domain: string} $analytics
 * @var array<string, string> $analyticsErrors
 * @var string $adminPath
 */

use Gate\Core\Html;
use Gate\Http\Controllers\Admin\MaintenanceController;

$base = $adminPath . '/settings/maintenance';
$reportLine = static function (array $report) use ($view): string {
    $mail = $report['mail'] ?? null;
    $parts = [
        $view->t('admin.maintenance.report_mail') . ': ' . (is_array($mail) ? $view->t('admin.maintenance.report_mail_sent', ['sent' => (int) ($mail['sent'] ?? 0), 'failed' => (int) ($mail['failed'] ?? 0)]) : $view->t('admin.maintenance.report_mail_off')),
        $view->t('admin.maintenance.report_backup') . ': ' . (is_string($report['backup'] ?? null) ? $report['backup'] : '—'),
    ];
    return implode(' · ', $parts);
};
$backupRows = [];
foreach ($backups as $backup) {
    $backupRows[] = [
        'file' => $backup['name'],
        'at' => $backup['at'],
        'size' => MaintenanceController::formatBytes($backup['size']),
        'download' => Html::trusted('<a class="link-sm" href="' . e_url($base . '/backups/' . rawurlencode($backup['name'])) . '" download>' . $view->component('icon', ['icon' => 'fa-solid fa-download', 'size' => '14']) . ' ' . e($view->t('admin.maintenance.download')) . '</a>'),
    ];
}
$backupOptions = array_map(static fn (array $b): array => ['value' => $b['name'], 'label' => $b['at'] . ' — ' . $b['name']], $backups);
$totalFindings = array_sum(array_map('count', $findings));
$providers = [
    ['value' => 'none', 'label' => $view->t('admin.maintenance.analytics_none')],
    ['value' => 'ga4', 'label' => 'Google Analytics 4'],
    ['value' => 'plausible', 'label' => 'Plausible'],
];
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<div class="flex gap-20 agrow">
  <section class="acard agrow sec-card maint-card" id="scheduler" aria-labelledby="scheduler-title">
    <div class="sec-card__head"><h2 class="h3" id="scheduler-title"><?= e($view->t('admin.maintenance.scheduler_title')) ?></h2><?= $view->component('status-pill', ['label' => $lastRun === null ? $view->t('admin.maintenance.scheduler_never') : $view->t('admin.maintenance.scheduler_last', ['when' => $lastRun]), 'tone' => $lastRun === null ? 'diagnosis' : 'confirmed', 'dot' => $lastRun !== null]) ?></div>
    <p class="muted maint-card__text"><?= e($view->t('admin.maintenance.scheduler_desc')) ?></p>
    <?= $view->component('input', ['name' => 'scheduler_url', 'id' => 'scheduler-url', 'label' => $view->t('admin.maintenance.scheduler_url'), 'value' => $schedulerUrl, 'readonly' => true, 'hint' => $view->t('admin.maintenance.scheduler_url_hint')]) ?>

<?php if ($lastReport !== null): ?>
    <p class="muted maint-card__text"><?= e($reportLine($lastReport)) ?></p>
<?php endif; ?>
    <div class="actions">
      <form method="post" action="<?= e_url($base . '/scheduler/regenerate') ?>" data-confirm="<?= e_attr($view->t('admin.maintenance.scheduler_regenerate_confirm')) ?>" data-confirm-title="<?= e_attr($view->t('admin.maintenance.scheduler_regenerate')) ?>" data-confirm-ok="<?= e_attr($view->t('admin.maintenance.scheduler_regenerate')) ?>">
        <?= $view->csrfField() ?>
        <?= $view->component('button', ['label' => $view->t('admin.maintenance.scheduler_regenerate'), 'variant' => 'admin-secondary', 'type' => 'submit']) ?>
      </form>
      <form method="post" action="<?= e_url($base . '/scheduler/run') ?>">
        <?= $view->csrfField() ?>
        <?= $view->component('button', ['label' => $view->t('admin.maintenance.scheduler_run'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-play', 'iconPosition' => 'start']) ?>
      </form>
    </div>
  </section>
  <div class="side-400 maint-side">
    <section class="acard panel-fields" id="monitor" aria-labelledby="monitor-title">
      <div class="card-intro card-intro--flush"><h2 class="h3" id="monitor-title"><?= e($view->t('admin.maintenance.monitor_title')) ?></h2><span class="muted"><?= e($view->t('admin.maintenance.monitor_desc')) ?></span></div>
<?php if ($healthUrl !== null): ?>
      <?= $view->component('input', ['name' => 'health_url', 'id' => 'health-url', 'label' => $view->t('admin.maintenance.monitor_url'), 'value' => $healthUrl, 'readonly' => true]) ?>
<?php else: ?>
      <?= $view->component('notice', ['title' => $view->t('admin.maintenance.monitor_missing'), 'text' => $view->t('admin.maintenance.monitor_missing_text')]) ?>
<?php endif; ?>
    </section>
  </div>
</div>

<section class="acard sec-card maint-card" id="backups" aria-labelledby="backups-title">
  <div class="sec-card__head"><h2 class="h3" id="backups-title"><?= e($view->t('admin.maintenance.backups_title')) ?></h2>
    <form method="post" action="<?= e_url($base . '/backups') ?>">
      <?= $view->csrfField() ?>
      <?= $view->component('button', ['label' => $view->t('admin.maintenance.backup_now'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-box-archive', 'iconPosition' => 'start']) ?>
    </form>
  </div>
  <p class="muted maint-card__text"><?= e($view->t('admin.maintenance.backups_desc')) ?></p>
<?php if ($backupError !== null): ?>
  <?= $view->component('notice', ['title' => $view->t('admin.maintenance.backups_unavailable'), 'text' => $backupError, 'role' => 'alert']) ?>
<?php elseif ($lastBackupError !== ''): ?>
  <?= $view->component('notice', ['title' => $view->t('admin.maintenance.backup_last_failed'), 'text' => $lastBackupError, 'role' => 'alert']) ?>
<?php endif; ?>
  <?= $view->component('data-table', ['columns' => [
      ['key' => 'file', 'label' => $view->t('admin.maintenance.col_file')],
      ['key' => 'at', 'label' => $view->t('admin.maintenance.col_date')],
      ['key' => 'size', 'label' => $view->t('admin.maintenance.col_size')],
      ['key' => 'download', 'label' => $view->t('admin.maintenance.download'), 'hideLabel' => true],
  ], 'rows' => $backupRows, 'caption' => $view->t('admin.maintenance.backups_title'), 'empty' => $view->t('admin.maintenance.backups_empty')]) ?>

  <form class="maint-upload" method="post" action="<?= e_url($base . '/backups/upload') ?>" enctype="multipart/form-data">
    <?= $view->csrfField() ?>
    <label class="file-field">
      <span class="file-field__label"><?= e($view->t('admin.maintenance.upload_label', ['limit' => MaintenanceController::formatBytes($uploadLimit)])) ?></span>
      <input type="file" name="backup" accept=".gz,application/gzip" required>
    </label>
<?php if ($uploadError !== null): ?>
    <?= $view->component('form-error', ['message' => $uploadError]) ?>
<?php endif; ?>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.maintenance.upload_button'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-up-from-bracket', 'iconPosition' => 'start']) ?></div>
  </form>
</section>

<section class="acard sec-card maint-card" id="restore" aria-labelledby="restore-title">
  <div class="sec-card__intro"><h2 class="h3" id="restore-title"><?= e($view->t('admin.maintenance.restore_title')) ?></h2><span class="muted"><?= e($view->t('admin.maintenance.restore_desc')) ?></span></div>
<?php if ($restoreError !== null): ?>
  <?= $view->component('notice', ['title' => $view->t('admin.maintenance.restore_not_done'), 'text' => $restoreError, 'role' => 'alert']) ?>
<?php endif; ?>
<?php if ($backups === []): ?>
  <p class="muted maint-card__text"><?= e($view->t('admin.maintenance.backups_empty')) ?></p>
<?php else: ?>
  <form method="post" action="<?= e_url($base . '/backups/restore') ?>" data-confirm="<?= e_attr($view->t('admin.maintenance.restore_confirm')) ?>" data-confirm-title="<?= e_attr($view->t('admin.maintenance.restore_button')) ?>" data-confirm-ok="<?= e_attr($view->t('admin.maintenance.restore_button')) ?>" data-confirm-tone="danger" novalidate>
    <?= $view->csrfField() ?>
    <div class="fields-3 fields-3--sec">
      <?= $view->component('select', ['name' => 'file', 'id' => 'restore-file', 'label' => $view->t('admin.maintenance.restore_file'), 'options' => $backupOptions, 'value' => $backupOptions[0]['value']]) ?>
      <?= $view->component('input', ['name' => 'password', 'id' => 'restore-password', 'type' => 'password', 'label' => $view->t('admin.security.confirm_password'), 'autocomplete' => 'current-password', 'required' => true]) ?>
      <?= $view->component('input', ['name' => 'confirm', 'id' => 'restore-confirm', 'label' => $view->t('admin.maintenance.restore_type_label'), 'autocomplete' => 'off', 'placeholder' => 'RESTORE', 'required' => true]) ?>
    </div>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.maintenance.restore_button'), 'variant' => 'admin-danger', 'type' => 'submit', 'icon' => 'fa-solid fa-clock-rotate-left', 'iconPosition' => 'start']) ?></div>
  </form>
<?php endif; ?>
</section>

<div class="flex gap-20 agrow">
  <section class="acard agrow sec-card maint-card" id="content-check" aria-labelledby="check-title">
    <div class="sec-card__head"><h2 class="h3" id="check-title"><?= e($view->t('admin.maintenance.check_title')) ?></h2><?= $view->component('status-pill', ['label' => $totalFindings === 0 ? $view->t('admin.maintenance.check_clean') : $view->t('admin.maintenance.check_count', ['n' => $totalFindings]), 'tone' => $totalFindings === 0 ? 'confirmed' : 'diagnosis', 'dot' => $totalFindings === 0]) ?></div>
    <p class="muted maint-card__text"><?= e($view->t('admin.maintenance.check_desc')) ?></p>
<?php foreach ($findings as $group => $items): ?>
<?php if ($items === []) { continue; } ?>
    <h3 class="maint-card__group"><?= e($group === 'settings' ? $view->t('admin.maintenance.check_settings') : $view->t('admin.maintenance.check_language', ['lang' => strtoupper($group)])) ?></h3>
    <ul class="maint-list">
<?php foreach ($items as $item): ?>
      <li><?= e($item) ?></li>
<?php endforeach; ?>
    </ul>
<?php endforeach; ?>
  </section>
  <div class="side-400 maint-side">
    <form class="acard panel-fields" id="analytics" method="post" action="<?= e_url($base . '/analytics') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.maintenance.analytics_title')) ?></h2><span class="muted"><?= e($view->t('admin.maintenance.analytics_desc')) ?></span></div>
      <?= $view->component('select', ['name' => 'provider', 'id' => 'analytics-provider', 'label' => $view->t('admin.maintenance.analytics_provider'), 'options' => $providers, 'value' => $analytics['provider'], 'error' => $analyticsErrors['provider'] ?? null]) ?>
      <?= $view->component('input', ['name' => 'ga4_id', 'id' => 'analytics-ga4', 'label' => $view->t('admin.maintenance.analytics_ga4'), 'value' => $analytics['ga4_id'], 'placeholder' => 'G-XXXXXXXXXX', 'error' => $analyticsErrors['ga4_id'] ?? null]) ?>
      <?= $view->component('input', ['name' => 'plausible_domain', 'id' => 'analytics-domain', 'label' => $view->t('admin.maintenance.analytics_domain'), 'value' => $analytics['plausible_domain'], 'placeholder' => 'gatelebanon.org', 'error' => $analyticsErrors['plausible_domain'] ?? null]) ?>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
</div>
