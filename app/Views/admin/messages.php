<?php
/**
 * Messages: the contact form inbox. Tabs (inbox, unread, archive), search and subject filter, the list with the
 * opening line of each message, pagination and a CSV export of what is shown.
 *
 * @var \Gate\Core\View $view
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} $result
 * @var list<array{id: int, when: string, who: string, subject: string, excerpt: string, unread: bool, archived: bool}> $rows
 * @var array<string, string> $filters
 * @var string $box
 * @var int $unreadCount
 * @var string $query
 * @var list<string> $subjects
 * @var bool $canManage
 * @var string $adminPath
 */

use Gate\Core\Html;

$tableRows = [];
foreach ($rows as $row) {
    $actions = [];
    if ($canManage && !$row['unread'] && !$row['archived']) {
        $actions[] = ['action' => $adminPath . '/messages/' . $row['id'] . '/unread', 'label' => $view->t('admin.messages.mark_unread'), 'icon' => 'fa-regular fa-envelope'];
    }
    if ($canManage) {
        $actions[] = ['action' => $adminPath . '/messages/' . $row['id'] . '/archive', 'label' => $view->t($row['archived'] ? 'admin.messages.restore' : 'admin.messages.archive'), 'icon' => 'fa-solid fa-box-archive'];
    }
    $tableRows[] = [
        'when' => $row['when'],
        'who' => Html::trusted(($row['unread'] ? '<span class="dot-unread" aria-hidden="true"></span><span class="visually-hidden">' . e($view->t('admin.messages.unread')) . '</span> ' : '') . '<a class="link-sm" href="' . e_url($adminPath . '/messages/' . $row['id']) . '">' . e($row['who']) . '</a>'),
        'subject' => $view->component('status-pill', ['label' => $row['subject']]),
        'excerpt' => $row['excerpt'],
        'actions' => $actions === [] ? '' : $view->component('row-actions', ['actions' => $actions]),
    ];
}
$boxHref = static fn (string $b): string => $adminPath . '/messages' . ($b === 'inbox' ? '' : '?box=' . $b);
$subjectOptions = [['value' => '', 'label' => $view->t('admin.messages.all_subjects')]];
foreach ($subjects as $s) {
    $subjectOptions[] = ['value' => $s, 'label' => $view->t('site.form.subjects.' . $s)];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.messages.title'), 'items' => [
    ['label' => $view->t('admin.messages.box_inbox'), 'href' => $boxHref('inbox'), 'active' => $box === 'inbox'],
    ['label' => $view->t('admin.messages.box_unread'), 'href' => $boxHref('unread'), 'active' => $box === 'unread', 'count' => $unreadCount],
    ['label' => $view->t('admin.messages.box_archive'), 'href' => $boxHref('archive'), 'active' => $box === 'archive'],
]]) ?>

<div class="acard agrow col">
  <form class="filters-row" method="get" action="<?= e_url($adminPath . '/messages') ?>">
<?php if ($box !== 'inbox'): ?>
    <input type="hidden" name="box" value="<?= e_attr($box) ?>">
<?php endif; ?>
    <?= $view->component('input', ['name' => 'q', 'label' => $view->t('admin.messages.search'), 'value' => $filters['q'] ?? '', 'type' => 'search']) ?>

    <?= $view->component('select', ['name' => 'subject', 'id' => 'filter-subject', 'label' => $view->t('site.form.subject'), 'value' => $filters['subject'] ?? '', 'options' => $subjectOptions]) ?>

    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.messages.filter'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.messages.export'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/messages/export' . $query, 'icon' => 'fa-solid fa-download', 'iconPosition' => 'start']) ?>

    </div>
  </form>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.messages.title'),
      'columns' => [
          ['key' => 'when', 'label' => $view->t('admin.messages.received'), 'class' => 't-muted'],
          ['key' => 'who', 'label' => $view->t('admin.messages.from'), 'class' => 't-strong'],
          ['key' => 'subject', 'label' => $view->t('site.form.subject')],
          ['key' => 'excerpt', 'label' => $view->t('site.form.message'), 'class' => 't-muted'],
          ['key' => 'actions', 'label' => $view->t('admin.messages.actions'), 'hideLabel' => true],
      ],
      'rows' => $tableRows,
      'empty' => $view->component('empty-state', ['title' => $view->t('admin.messages.empty_title'), 'text' => $view->t($box === 'archive' ? 'admin.messages.empty_archive' : 'admin.messages.empty_text'), 'icon' => 'fa-regular fa-envelope']),
  ]) ?>

<?php if ($result['pages'] > 1): ?>
  <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $adminPath . '/messages' . ($query === '' ? '?' : $query . '&') . 'page={page}']) ?>

<?php endif; ?>
</div>
