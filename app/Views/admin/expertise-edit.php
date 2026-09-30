<?php
/**
 * Content → Areas of expertise → one area: icon, cover image, visibility and the texts of one language.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, key: string, icon: string, is_enabled: bool, show_on_home: bool, sort_order: int, cover: string} $area
 * @var list<array{value: string, label: string}> $images
 * @var string $lang
 * @var array<string, string> $values
 * @var bool $published
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var list<string> $icons
 * @var array<string, string> $errors
 * @var bool $canEdit
 * @var string $adminPath
 */
$iconOptions = array_map(static fn (string $icon): array => ['value' => $icon, 'label' => $view->t('admin.expertise.icons.' . $icon)], $icons);
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<div class="flex gap-20 agrow">
  <form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/expertise/' . $area['id']) ?>" novalidate>
    <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
    <div class="sec-card__head">
      <h2 class="h3"><?= e($area['key']) ?> · <?= e(strtoupper($lang)) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t($published ? 'admin.content.published' : 'admin.content.draft'), 'tone' => $published ? 'confirmed' : 'diagnosis', 'dot' => $published]) ?>

    </div>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.pages.page_title'), 'value' => $values['title'], 'error' => $errors['title'] ?? null, 'required' => true, 'disabled' => !$canEdit]) ?>

      <?= $view->component('input', ['name' => 'slug', 'label' => $view->t('admin.pages.slug'), 'value' => $values['slug'], 'error' => $errors['slug'] ?? null, 'hint' => $view->t('admin.pages.slug_hint'), 'disabled' => !$canEdit]) ?>

      <?= $view->component('select', ['name' => 'icon', 'id' => 'expertise-icon', 'label' => $view->t('admin.expertise.icon'), 'value' => $area['icon'], 'options' => $iconOptions, 'error' => $errors['icon'] ?? null, 'disabled' => !$canEdit]) ?>

      <?= $view->component('select', ['name' => 'cover', 'id' => 'expertise-cover', 'label' => $view->t('admin.expertise.cover'), 'value' => $area['cover'], 'options' => $images, 'hint' => $view->t('admin.media.pick_hint'), 'disabled' => !$canEdit, 'attrs' => ['data-media-preview' => 'image']]) ?>

    </div>
    <?= $view->component('textarea', ['name' => 'summary', 'id' => 'expertise-summary', 'label' => $view->t('admin.expertise.summary'), 'value' => $values['summary'], 'rows' => 3, 'maxlength' => 400, 'disabled' => !$canEdit]) ?>

    <?= $view->component('textarea', ['name' => 'body', 'id' => 'expertise-body', 'label' => $view->t('admin.pages.body'), 'value' => $values['body'], 'rows' => 12, 'hint' => $view->t('admin.pages.body_hint'), 'disabled' => !$canEdit]) ?>

    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'meta_title', 'label' => $view->t('admin.pages.meta_title'), 'value' => $values['meta_title'], 'disabled' => !$canEdit]) ?>

      <?= $view->component('input', ['name' => 'meta_description', 'label' => $view->t('admin.pages.meta_description'), 'value' => $values['meta_description'], 'maxlength' => 320, 'disabled' => !$canEdit]) ?>

    </div>
    <?= $view->component('setting-row', ['name' => $view->t('admin.content.publish'), 'description' => $view->t('admin.content.publish_desc'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'is_published', 'label' => $view->t('admin.content.publish'), 'checked' => $published, 'disabled' => !$canEdit])]) ?>

    <?= $view->component('setting-row', ['name' => $view->t('admin.expertise.enabled'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'is_enabled', 'label' => $view->t('admin.expertise.enabled'), 'checked' => $area['is_enabled'], 'disabled' => !$canEdit])]) ?>

    <?= $view->component('setting-row', ['name' => $view->t('admin.expertise.on_home'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'show_on_home', 'label' => $view->t('admin.expertise.on_home'), 'checked' => $area['show_on_home'], 'disabled' => !$canEdit])]) ?>

    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'sort_order', 'type' => 'number', 'label' => $view->t('admin.expertise.order'), 'value' => (string) $area['sort_order'], 'disabled' => !$canEdit]) ?>

    </div>
<?php if ($canEdit): ?>
    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.common.back'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/expertise']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
<?php endif; ?>
  </form>
<?php if ($canEdit): ?>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.content.remove')) ?></h2><span class="muted"><?= e($view->t('admin.expertise.delete_confirm')) ?></span></div>
      <?= $view->component('row-actions', ['actions' => [
          ['action' => $adminPath . '/expertise/' . $area['id'] . '/delete', 'label' => $view->t('admin.expertise.deleted'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.expertise.delete_confirm'), 'confirmTitle' => $view->t('admin.content.remove')],
      ]]) ?>

    </div>
  </div>
<?php endif; ?>
</div>
