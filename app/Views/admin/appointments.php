<?php
/**
 * Appointments: the workflow list with status filters, a date range, search, sorting, pagination and CSV export.
 *
 * @var \BMMatic\Core\View $view
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} $result
 * @var list<array{id: int, when: string, who: string, car: string, box: string, label: string, tone: string, unread: bool}> $rows
 * @var array<string, mixed> $filters
 * @var array<string, int> $counts
 * @var string $query
 * @var list<string> $statuses
 * @var string $adminPath
 */

use BMMatic\Core\Html;

$filterValue = static fn (string $key): string => is_scalar($filters[$key] ?? null) ? (string) $filters[$key] : '';
$tableRows = [];
foreach ($rows as $row) {
    $tableRows[] = [
        'when' => $row['when'],
        'who' => Html::trusted('<a class="link-sm" href="' . e_url($adminPath . '/appointments/' . $row['id']) . '">' . e($row['who']) . '</a>' . ($row['unread'] ? ' <span class="dot-unread" aria-hidden="true"></span><span class="visually-hidden">' . e($view->t('admin.appointments.unread')) . '</span>' : '')),
        'car' => $row['car'],
        'box' => $view->component('status-pill', ['label' => $row['box']]),
        'status' => $view->component('status-pill', ['label' => $row['label'], 'tone' => $row['tone']]),
    ];
}
$statusTabs = [['label' => $view->t('admin.common.all'), 'href' => $adminPath . '/appointments', 'active' => $filterValue('status') === '', 'count' => array_sum($counts)]];
foreach ($statuses as $status) {
    $statusTabs[] = [
        'label' => $view->t('admin.statuses.' . $status),
        'href' => $adminPath . '/appointments?status=' . $status,
        'active' => $filterValue('status') === $status,
        'count' => $counts[$status] ?? 0,
    ];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.appointments.status'), 'items' => $statusTabs]) ?>

<div class="acard agrow col">
  <div class="card-head">
    <h2 class="h3"><?= e($view->t('admin.appointments.card_title')) ?></h2>
    <a class="link-sm" href="<?= e_url($adminPath . '/appointments/export' . ($query === '' ? '' : $query)) ?>"><?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-up-from-bracket', 'size' => '14']) ?> <?= e($view->t('admin.common.export_csv')) ?></a>
  </div>
  <form class="filters filters--form" method="get" action="<?= e_url($adminPath . '/appointments') ?>">
<?php if ($filterValue('status') !== ''): ?>
    <input type="hidden" name="status" value="<?= e_attr($filterValue('status')) ?>">
<?php endif; ?>
    <?= $view->component('input', ['name' => 'q', 'label' => $view->t('admin.common.search'), 'value' => $filterValue('q'), 'placeholder' => $view->t('admin.common.search_placeholder'), 'type' => 'search']) ?>

    <?= $view->component('input', ['name' => 'from', 'type' => 'date', 'label' => $view->t('admin.common.from'), 'value' => $filterValue('from')]) ?>

    <?= $view->component('input', ['name' => 'to', 'type' => 'date', 'label' => $view->t('admin.common.to'), 'value' => $filterValue('to')]) ?>

    <div class="actions actions--end">
      <?= $view->component('button', ['label' => $view->t('admin.common.reset'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/appointments']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.common.apply'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?>

    </div>
  </form>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.appointments.card_title'),
      'columns' => [
          ['key' => 'when', 'label' => $view->t('admin.appointments.received'), 'class' => 't-muted'],
          ['key' => 'who', 'label' => $view->t('admin.appointments.customer'), 'class' => 't-strong'],
          ['key' => 'car', 'label' => $view->t('admin.appointments.vehicle')],
          ['key' => 'box', 'label' => $view->t('site.form.type')],
          ['key' => 'status', 'label' => $view->t('admin.appointments.status')],
      ],
      'rows' => $tableRows,
      'empty' => $view->component('empty-state', [
          'title' => $view->t($result['total'] === 0 && $query === '' ? 'admin.appointments.empty_title' : 'admin.appointments.empty_filtered'),
          'text' => $query === '' ? $view->t('admin.appointments.empty_text') : null,
          'icon' => 'fa-regular fa-calendar',
      ]),
  ]) ?>

<?php if ($result['pages'] > 1): ?>
  <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $adminPath . '/appointments' . ($query === '' ? '?' : $query . '&') . 'page={page}']) ?>

<?php endif; ?>
</div>
