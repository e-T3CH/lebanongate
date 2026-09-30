<?php
/**
 * Content → Pages: the system pages (child pages under their parent) with their translation state per language.
 *
 * @var \Gate\Core\View $view
 * @var list<array{id: int, key: string, child: bool, parent: string, title: string, enabled: bool, in_nav: bool, updated: string, states: array<string, string>}> $pages
 * @var list<string> $languages
 * @var bool $canEdit
 * @var string $adminPath
 */

use Gate\Core\Html;

$rows = [];
foreach ($pages as $page) {
    $states = '';
    foreach ($languages as $lang) {
        $state = $page['states'][$lang] ?? 'missing';
        $states .= (string) $view->component('status-pill', [
            'label' => strtoupper($lang),
            'tone' => $state === 'published' ? 'confirmed' : ($state === 'draft' ? 'diagnosis' : 'neutral'),
            'attrs' => ['title' => $view->t('admin.content.' . $state)],
        ]);
    }
    $rows[] = [
        'title' => Html::trusted(($page['child'] ? '<span class="muted" aria-hidden="true">↳ </span>' : '') . '<a class="link-sm" href="' . e_url($adminPath . '/pages/' . $page['id']) . '">' . e($page['title']) . '</a>'),
        'key' => $page['key'],
        'states' => Html::trusted('<div class="pill-row">' . $states . '</div>'),
        'nav' => $page['in_nav'] ? $view->component('icon', ['icon' => 'fa-solid fa-check', 'size' => '14', 'class' => 'ic-accent']) : '—',
        'status' => $view->component('status-pill', ['label' => $view->t($page['enabled'] ? 'admin.users.active' : 'admin.users.inactive'), 'tone' => $page['enabled'] ? 'confirmed' : 'neutral', 'dot' => $page['enabled']]),
        'updated' => $page['updated'],
    ];
}
?>
<div class="acard agrow col">
  <div class="card-head">
    <h2 class="h3"><?= e($view->t('admin.pages.card_title')) ?></h2>
  </div>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.pages.card_title'),
      'columns' => [
          ['key' => 'title', 'label' => $view->t('admin.pages.page'), 'class' => 't-strong'],
          ['key' => 'key', 'label' => $view->t('admin.expertise.key'), 'class' => 't-muted'],
          ['key' => 'states', 'label' => $view->t('admin.pages.translations')],
          ['key' => 'nav', 'label' => $view->t('admin.pages.in_nav')],
          ['key' => 'status', 'label' => $view->t('admin.users.status')],
          ['key' => 'updated', 'label' => $view->t('admin.pages.updated'), 'class' => 't-muted'],
      ],
      'rows' => $rows,
      'empty' => $view->component('empty-state', ['title' => $view->t('admin.pages.card_title'), 'icon' => 'fa-regular fa-file-lines']),
  ]) ?>

</div>
<div class="acard agrow col">
  <div class="card-head"><h2 class="h3"><?= e($view->t('admin.lists.subtitle')) ?></h2></div>
  <div class="card-rows row-actions">
<?php foreach (['stats' => 'stats_title', 'partners' => 'partners_title'] as $type => $key): ?>
    <?= $view->component('button', ['label' => $view->t('admin.lists.' . $key), 'variant' => 'admin-secondary', 'href' => $adminPath . '/content/' . $type, 'icon' => 'fa-solid fa-arrow-right', 'class' => 'btn-row']) ?>

<?php endforeach; ?>
  </div>
</div>
