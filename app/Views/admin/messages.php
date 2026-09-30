<?php
/**
 * Messages: the inbox view of the same requests. Unread first, with the opening line of the symptoms; replying
 * opens the mail program of the user (the panel never sends a free-text email itself).
 *
 * @var \Gate\Core\View $view
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} $result
 * @var list<array{id: int, when: string, who: string, car: string, excerpt: string, unread: bool, mailto: string}> $rows
 * @var bool $unreadOnly
 * @var int $unreadCount
 * @var string $query
 * @var bool $canManage
 * @var string $adminPath
 */

use Gate\Core\Html;

$tableRows = [];
foreach ($rows as $row) {
    $actions = [];
    if ($canManage && !$row['unread']) {
        $actions[] = ['action' => $adminPath . '/appointments/' . $row['id'] . '/unread', 'label' => $view->t('admin.appointments.mark_unread'), 'icon' => 'fa-regular fa-envelope', 'fields' => ['return' => 'messages']];
    }
    $tableRows[] = [
        'when' => $row['when'],
        'who' => Html::trusted(($row['unread'] ? '<span class="dot-unread" aria-hidden="true"></span><span class="visually-hidden">' . e($view->t('admin.appointments.unread')) . '</span> ' : '') . '<a class="link-sm" href="' . e_url($adminPath . '/appointments/' . $row['id']) . '">' . e($row['who']) . '</a>'),
        'car' => $row['car'],
        'excerpt' => $row['excerpt'],
        'actions' => $actions === [] ? '' : $view->component('row-actions', ['actions' => $actions]),
    ];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.messages.card_title'), 'items' => [
    ['label' => $view->t('admin.messages.unread_only'), 'href' => $adminPath . '/messages', 'active' => $unreadOnly, 'count' => $unreadCount],
    ['label' => $view->t('admin.messages.all'), 'href' => $adminPath . '/messages?unread=0', 'active' => !$unreadOnly],
]]) ?>

<div class="acard agrow col">
  <div class="card-head"><h2 class="h3"><?= e($view->t('admin.messages.card_title')) ?></h2><a class="link-sm" href="<?= e_url($adminPath . '/appointments') ?>"><?= e($view->t('admin.dashboard.all_requests')) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'size' => '14', 'class' => 'ic-ne']) ?></a></div>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.messages.card_title'),
      'columns' => [
          ['key' => 'when', 'label' => $view->t('admin.appointments.received'), 'class' => 't-muted'],
          ['key' => 'who', 'label' => $view->t('admin.appointments.customer'), 'class' => 't-strong'],
          ['key' => 'car', 'label' => $view->t('admin.appointments.vehicle')],
          ['key' => 'excerpt', 'label' => $view->t('admin.appointments.symptoms'), 'class' => 't-muted'],
          ['key' => 'actions', 'label' => $view->t('admin.messages.open'), 'hideLabel' => true],
      ],
      'rows' => $tableRows,
      'empty' => $view->component('empty-state', ['title' => $view->t('admin.messages.empty_title'), 'text' => $view->t('admin.messages.empty_text'), 'icon' => 'fa-regular fa-envelope']),
  ]) ?>

<?php if ($result['pages'] > 1): ?>
  <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $adminPath . '/messages' . ($query === '' ? '?' : $query . '&') . 'page={page}']) ?>

<?php endif; ?>
</div>
