<?php
/**
 * Content → Pages → one page: the texts and SEO fields of one language, the page settings, and on the home page the
 * order and visibility of its sections.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, key: string, template: string, is_enabled: bool, in_nav: bool, nav_order: int, in_sitemap: bool, hero: string, child: bool} $page
 * @var list<array{value: string, label: string}> $images
 * @var string $lang
 * @var array<string, string> $values
 * @var bool $published
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var array<string, string> $errors
 * @var list<array{id: int, type: string, name: string, enabled: bool, locked: bool, title: string}> $sections
 * @var bool $canEdit
 * @var string $previewUrl
 * @var string $adminPath
 */
$isHome = $page['key'] === 'home';
$value = static fn (string $key): string => $values[$key] ?? '';
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<div class="flex gap-20 agrow">
  <form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/pages/' . $page['id']) ?>" novalidate>
    <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
    <div class="sec-card__head">
      <h2 class="h3"><?= e($view->t('admin.pages.page')) ?> · <?= e(strtoupper($lang)) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t($published ? 'admin.content.published' : 'admin.content.draft'), 'tone' => $published ? 'confirmed' : 'diagnosis', 'dot' => $published]) ?>

    </div>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.pages.page_title'), 'value' => $value('title'), 'error' => $errors['title'] ?? null, 'required' => true, 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

      <?= $view->component('input', ['name' => 'highlight', 'label' => $view->t('admin.pages.highlight'), 'value' => $value('highlight'), 'hint' => $view->t('admin.pages.highlight_hint'), 'disabled' => !$canEdit]) ?>

<?php if (!$isHome): ?>
      <?= $view->component('input', ['name' => 'slug', 'label' => $view->t('admin.pages.slug'), 'value' => $value('slug'), 'error' => $errors['slug'] ?? null, 'hint' => $view->t('admin.pages.slug_hint'), 'disabled' => !$canEdit]) ?>

<?php endif; ?>
      <?= $view->component('input', ['name' => 'nav_label', 'label' => $view->t('admin.pages.nav_label'), 'value' => $value('nav_label'), 'disabled' => !$canEdit]) ?>

      <?= $view->component('input', ['name' => 'label', 'label' => $view->t('admin.pages.label'), 'value' => $value('label'), 'disabled' => !$canEdit]) ?>

    </div>
    <?= $view->component('textarea', ['name' => 'intro', 'id' => 'page-intro', 'label' => $view->t('admin.pages.intro'), 'value' => $value('intro'), 'rows' => 3, 'maxlength' => 2000, 'disabled' => !$canEdit]) ?>

<?php if (in_array($page['template'], ['text', 'about', 'partners'], true)): ?>
    <?= $view->component('textarea', ['name' => 'body', 'id' => 'page-body', 'label' => $view->t('admin.pages.body'), 'value' => $value('body'), 'rows' => 14, 'hint' => $view->t('admin.pages.body_hint'), 'disabled' => !$canEdit]) ?>

<?php endif; ?>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'meta_title', 'label' => $view->t('admin.pages.meta_title'), 'value' => $value('meta_title'), 'disabled' => !$canEdit]) ?>

      <?= $view->component('input', ['name' => 'meta_description', 'label' => $view->t('admin.pages.meta_description'), 'value' => $value('meta_description'), 'maxlength' => 320, 'disabled' => !$canEdit]) ?>

    </div>
    <?= $view->component('setting-row', ['name' => $view->t('admin.content.publish'), 'description' => $view->t('admin.content.publish_desc'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'is_published', 'label' => $view->t('admin.content.publish'), 'checked' => $published, 'disabled' => !$canEdit])]) ?>

    <?= $view->component('setting-row', ['name' => $view->t('admin.pages.enabled'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'is_enabled', 'label' => $view->t('admin.pages.enabled'), 'checked' => $page['is_enabled'], 'disabled' => !$canEdit])]) ?>

    <?= $view->component('setting-row', ['name' => $view->t('admin.pages.in_nav'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'in_nav', 'label' => $view->t('admin.pages.in_nav'), 'checked' => $page['in_nav'], 'disabled' => !$canEdit])]) ?>

    <?= $view->component('setting-row', ['name' => $view->t('admin.pages.in_sitemap'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'in_sitemap', 'label' => $view->t('admin.pages.in_sitemap'), 'checked' => $page['in_sitemap'], 'disabled' => !$canEdit])]) ?>

    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'nav_order', 'type' => 'number', 'label' => $view->t('admin.pages.nav_order'), 'value' => (string) $page['nav_order'], 'disabled' => !$canEdit]) ?>

<?php if (!$isHome): ?>
      <?= $view->component('select', ['name' => 'hero', 'id' => 'page-hero', 'label' => $view->t('admin.pages.hero'), 'value' => $page['hero'], 'options' => $images, 'hint' => $view->t('admin.pages.hero_hint'), 'disabled' => !$canEdit, 'attrs' => ['data-media-preview' => 'image']]) ?>

<?php endif; ?>
    </div>
<?php if ($canEdit): ?>
    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.content.preview'), 'variant' => 'admin-secondary', 'href' => $previewUrl, 'icon' => 'fa-solid fa-arrow-right', 'iconClass' => 'ic-ne', 'attrs' => ['target' => '_blank', 'rel' => 'noopener']]) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
<?php endif; ?>
  </form>
</div>
<?php if ($sections !== []): ?>
  <div class="sections-wide">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/pages/' . $page['id'] . '/sections') ?>">
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.pages.sections_title')) ?></h2><span class="muted"><?= e($view->t('admin.pages.sections_desc')) ?></span></div>
      <ul class="sortable" data-sortable aria-label="<?= e_attr($view->t('admin.pages.sections_title')) ?>">
<?php foreach ($sections as $section): ?>
        <li class="sortable__item" data-id="<?= e_attr((string) $section['id']) ?>"<?= $section['locked'] ? ' data-locked' : '' ?>>
          <input type="hidden" name="order[]" value="<?= e_attr((string) $section['id']) ?>">
          <span class="sortable__handle"<?= $section['locked'] ? '' : ' draggable="true"' ?> aria-hidden="<?= $section['locked'] ? 'true' : 'false' ?>"><?= $view->component('icon', ['icon' => 'fa-solid fa-layer-group', 'size' => '14']) ?></span>
          <span class="sortable__label"><?= e($section['name']) ?><?php if ($section['title'] !== ''): ?><span class="muted sortable__sub"><?= e($section['title']) ?></span><?php endif; ?></span>
<?php if ($section['locked']): ?>
          <span class="muted"><?= e($view->t('admin.pages.section_locked')) ?></span>
<?php else: ?>
          <span class="sortable__moves">
            <?= $view->component('button', ['label' => $view->t('admin.content.move_up'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-up', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_up') . ': ' . $section['name'], 'attrs' => ['data-move' => 'up']]) ?>

            <?= $view->component('button', ['label' => $view->t('admin.content.move_down'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-down', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_down') . ': ' . $section['name'], 'attrs' => ['data-move' => 'down']]) ?>

          </span>
          <?= $view->component('toggle', ['name' => 'enabled[]', 'value' => (string) $section['id'], 'uncheckedValue' => null, 'label' => $view->t('admin.lists.visible') . ': ' . $section['name'], 'checked' => $section['enabled'], 'disabled' => !$canEdit]) ?>

<?php endif; ?>
          <a class="link-sm" href="<?= e_url($adminPath . '/pages/' . $page['id'] . '/sections/' . $section['id'] . '?lang=' . $lang) ?>"><?= e($view->t('admin.pages.edit_texts')) ?></a>
        </li>
<?php endforeach; ?>
      </ul>
      <span class="muted"><?= e($view->t('admin.content.drag_hint')) ?></span>
<?php if ($canEdit): ?>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.pages.save_order'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
    </form>
  </div>
<?php endif; ?>
