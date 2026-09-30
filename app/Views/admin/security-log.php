<?php
/**
 * Settings → Security log: what happened in the panel, filtered by event, user or date, with CSV export and the
 * retention setting.
 *
 * @var \BMMatic\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var list<array{when: string, event: string, label: string, user: string, ip: string, context: string}> $rows
 * @var list<string> $events
 * @var array<string, string> $filters
 * @var string $query
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $retention
 * @var list<int> $retentionChoices
 * @var string $adminPath
 */
$tableRows = [];
foreach ($rows as $row) {
    $tableRows[] = [
        'when' => $row['when'],
        'event' => $view->component('status-pill', ['label' => $row['label'], 'tone' => str_contains($row['event'], 'failed') || str_contains($row['event'], 'denied') || str_contains($row['event'], 'locked') ? 'danger' : 'neutral']),
        'user' => $row['user'],
        'ip' => $row['ip'],
        'details' => $row['context'],
    ];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head">
      <h2 class="h3"><?= e($view->t('admin.log.card_title')) ?></h2>
      <a class="link-sm" href="<?= e_url($adminPath . '/security/log/export' . $query) ?>"><?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-up-from-bracket', 'size' => '14']) ?> <?= e($view->t('admin.common.export_csv')) ?></a>
    </div>
    <form class="filters filters--form" method="get" action="<?= e_url($adminPath . '/security/log') ?>">
      <?= $view->component('select', ['name' => 'event', 'id' => 'log-event', 'label' => $view->t('admin.log.event'), 'value' => $filters['event'] ?? '', 'options' => array_merge(
          [['value' => '', 'label' => $view->t('admin.log.all_events')]],
          array_map(static fn (string $event): array => ['value' => $event, 'label' => $event], $events)
      )]) ?>

      <?= $view->component('input', ['name' => 'q', 'type' => 'search', 'label' => $view->t('admin.common.search'), 'value' => $filters['q'] ?? '', 'placeholder' => $view->t('admin.log.user')]) ?>

      <?= $view->component('input', ['name' => 'from', 'type' => 'date', 'label' => $view->t('admin.common.from'), 'value' => $filters['from'] ?? '']) ?>

      <?= $view->component('input', ['name' => 'to', 'type' => 'date', 'label' => $view->t('admin.common.to'), 'value' => $filters['to'] ?? '']) ?>

      <div class="actions actions--end">
        <?= $view->component('button', ['label' => $view->t('admin.common.reset'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/security/log']) ?>

        <?= $view->component('button', ['label' => $view->t('admin.common.apply'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?>

      </div>
    </form>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.log.card_title'),
        'columns' => [
            ['key' => 'when', 'label' => $view->t('admin.log.when'), 'class' => 't-muted'],
            ['key' => 'event', 'label' => $view->t('admin.log.event')],
            ['key' => 'user', 'label' => $view->t('admin.log.user'), 'class' => 't-strong'],
            ['key' => 'ip', 'label' => $view->t('admin.log.ip'), 'class' => 't-muted'],
            ['key' => 'details', 'label' => $view->t('admin.log.details'), 'class' => 't-muted'],
        ],
        'rows' => $tableRows,
        'empty' => $view->component('empty-state', ['title' => $view->t('admin.log.empty_title'), 'text' => $view->t('admin.log.empty_text'), 'icon' => 'fa-solid fa-shield-halved']),
    ]) ?>

<?php if ($pages > 1): ?>
    <?= $view->component('pagination', ['page' => $page, 'pages' => $pages, 'url' => $adminPath . '/security/log' . ($query === '' ? '?' : $query . '&') . 'page={page}']) ?>

<?php endif; ?>
  </div>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/security/log/retention') ?>">
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.log.retention_title')) ?></h2><span class="muted"><?= e($view->t('admin.log.retention_desc')) ?></span></div>
      <?= $view->component('select', ['name' => 'retention_days', 'id' => 'retention-days', 'label' => $view->t('admin.log.retention_days'), 'value' => (string) $retention, 'options' => array_map(static fn (int $days): array => ['value' => (string) $days, 'label' => $view->t('admin.log.retention_' . $days)], $retentionChoices)]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-secondary', 'type' => 'submit']) ?></div>
    </form>
    <div class="acard panel-fields">
      <span class="muted"><?= e($view->t('admin.common.results', ['count' => $total])) ?></span>
    </div>
  </div>
</div>
