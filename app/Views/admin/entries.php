<?php
/**
 * Projects / News / Publications / Albums: the list (newest first, search, language states, visibility) and a card
 * to add one by its title.
 *
 * @var \Gate\Core\View $view
 * @var string $type
 * @var string $base
 * @var list<array{id: int, title: string, date: string, status: string, statusLabel: string, region: string, is_enabled: bool, is_featured: bool, states: array<string, string>}> $rows
 * @var array{total: int, page: int, pages: int} $result
 * @var string $search
 * @var list<string> $languages
 * @var bool $canEdit
 * @var array<string, string> $errors
 * @var string $icon
 */

use Gate\Core\Html;

$tableRows = [];
foreach ($rows as $row) {
    $pills = '';
    foreach ($languages as $lang) {
        $state = $row['states'][$lang] ?? 'missing';
        $pills .= $view->component('status-pill', ['label' => strtoupper($lang), 'tone' => $state === 'published' ? 'confirmed' : ($state === 'draft' ? 'diagnosis' : 'neutral'), 'attrs' => ['title' => $view->t('admin.content.' . $state)]]);
    }
    $tableRows[] = [
        'title' => Html::trusted('<a class="link-sm" href="' . e_url($base . '/' . $row['id']) . '">' . e($row['title']) . '</a>' . ($row['is_featured'] ? ' <span class="muted">★</span>' : '')),
        'date' => $row['date'],
        'status' => $row['statusLabel'] !== '' ? $view->component('status-pill', ['label' => $row['statusLabel']]) : '',
        'languages' => Html::trusted('<span class="pill-row">' . $pills . '</span>'),
        'visible' => $view->component('status-pill', ['label' => $view->t($row['is_enabled'] ? 'admin.entries.visible' : 'admin.entries.hidden'), 'tone' => $row['is_enabled'] ? 'confirmed' : 'neutral', 'dot' => $row['is_enabled']]),
    ];
}
$columns = [
    ['key' => 'title', 'label' => $view->t('admin.entries.title_col'), 'class' => 't-strong'],
    ['key' => 'date', 'label' => $view->t('admin.entries.date'), 'class' => 't-muted'],
];
if (in_array($type, ['project', 'publication'], true)) {
    $columns[] = ['key' => 'status', 'label' => $view->t($type === 'project' ? 'site.entries.status' : 'site.entries.kind')];
}
$columns[] = ['key' => 'languages', 'label' => $view->t('admin.entries.languages')];
$columns[] = ['key' => 'visible', 'label' => $view->t('admin.entries.on_site')];
?>
<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <form class="filters-row" method="get" action="<?= e_url($base) ?>">
      <?= $view->component('input', ['name' => 'q', 'label' => $view->t('admin.entries.search'), 'value' => $search, 'type' => 'search']) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.messages.filter'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?></div>
    </form>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.entries.' . $type . '.title'),
        'columns' => $columns,
        'rows' => $tableRows,
        'empty' => $view->component('empty-state', ['title' => $view->t('admin.entries.' . $type . '.empty_title'), 'text' => $view->t('admin.entries.' . $type . '.empty_text'), 'icon' => $icon]),
    ]) ?>

<?php if ($result['pages'] > 1): ?>
    <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $base . '?' . ($search !== '' ? 'q=' . rawurlencode($search) . '&' : '') . 'page={page}']) ?>

<?php endif; ?>
  </div>
<?php if ($canEdit): ?>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($base . '/new') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.entries.' . $type . '.add')) ?></h2><span class="muted"><?= e($view->t('admin.entries.add_desc')) ?></span></div>
      <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.entries.title_field'), 'error' => $errors['title'] ?? null, 'required' => true]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.content.add'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-plus', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
<?php endif; ?>
</div>
