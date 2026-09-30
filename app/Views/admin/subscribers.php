<?php
/**
 * Newsletter subscribers: state tabs with counts, search, list, CSV export of the confirmed addresses.
 *
 * @var \Gate\Core\View $view
 * @var list<array{id: int, email: string, lang: string, since: string, state: string}> $rows
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} $result
 * @var string $state
 * @var string $search
 * @var array{pending: int, confirmed: int, unsubscribed: int} $counts
 * @var bool $enabled
 * @var string $adminPath
 */
$tabs = [];
foreach (['confirmed', 'pending', 'unsubscribed'] as $s) {
    $tabs[] = ['label' => $view->t('admin.subscribers.state_' . $s), 'href' => $adminPath . '/subscribers?state=' . $s, 'active' => $state === $s, 'count' => $counts[$s]];
}
$tableRows = [];
foreach ($rows as $row) {
    $tableRows[] = [
        'email' => $row['email'],
        'lang' => $row['lang'],
        'since' => $row['since'],
        'actions' => $view->component('row-actions', ['actions' => [
            ['action' => $adminPath . '/subscribers/' . $row['id'] . '/delete', 'label' => $view->t('admin.subscribers.delete'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.subscribers.delete_confirm'), 'confirmTitle' => $view->t('admin.subscribers.delete')],
        ]]),
    ];
}
?>
<?php if (!$enabled): ?>
<?= $view->component('notice', ['title' => $view->t('admin.subscribers.off_title'), 'text' => $view->t('admin.subscribers.off_text'), 'linkHref' => $adminPath . '/settings/general', 'linkLabel' => $view->t('admin.nav.settings') . ' →']) ?>

<?php endif; ?>
<?= $view->component('tabs', ['label' => $view->t('admin.subscribers.title'), 'items' => $tabs]) ?>

<div class="acard agrow col">
  <form class="filters-row" method="get" action="<?= e_url($adminPath . '/subscribers') ?>">
    <input type="hidden" name="state" value="<?= e_attr($state) ?>">
    <?= $view->component('input', ['name' => 'q', 'label' => $view->t('admin.subscribers.search'), 'value' => $search, 'type' => 'search']) ?>

    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.messages.filter'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.subscribers.export'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/subscribers/export', 'icon' => 'fa-solid fa-download', 'iconPosition' => 'start']) ?>

    </div>
  </form>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.subscribers.title'),
      'columns' => [
          ['key' => 'email', 'label' => $view->t('site.form.email'), 'class' => 't-strong'],
          ['key' => 'lang', 'label' => $view->t('admin.messages.language')],
          ['key' => 'since', 'label' => $view->t('admin.subscribers.since'), 'class' => 't-muted t-date'],
          ['key' => 'actions', 'label' => $view->t('admin.messages.actions'), 'hideLabel' => true],
      ],
      'rows' => $tableRows,
      'empty' => $view->component('empty-state', ['title' => $view->t('admin.subscribers.empty_title'), 'text' => $view->t('admin.subscribers.empty_text'), 'icon' => 'fa-solid fa-at']),
  ]) ?>

<?php if ($result['pages'] > 1): ?>
  <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $adminPath . '/subscribers?state=' . rawurlencode($state) . ($search !== '' ? '&q=' . rawurlencode($search) : '') . '&page={page}']) ?>

<?php endif; ?>
</div>
