<?php
/**
 * Content → Pages → home page → one section: the texts of that section in one language.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, type: string, page_id: int, locked: bool, enabled: bool} $section
 * @var string $lang
 * @var array<string, string> $values
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var bool $canEdit
 * @var string $adminPath
 * @var array<string, mixed> $extra the structured texts: About points and badge, the map's main region and notes
 * @var list<array{value: string, label: string}> $images
 * @var list<array{value: string, label: string}> $regions
 * @var list<string> $icons
 */
$type = $section['type'];
$pointIcons = array_map(static fn (string $icon): array => ['value' => $icon, 'label' => $view->t('admin.expertise.icons.' . $icon)], array_values(array_unique(array_merge(['check'], $icons))));
$points = is_array($extra['points'] ?? null) ? array_values($extra['points']) : [];
$notes = is_array($extra['notes'] ?? null) ? $extra['notes'] : [];
$str = static fn (mixed $v): string => is_string($v) ? $v : '';
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/pages/' . $section['page_id'] . '/sections/' . $section['id']) ?>" novalidate>
  <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
  <div class="sec-card__head">
    <h2 class="h3"><?= e($view->t('admin.sections.' . $section['type'])) ?> · <?= e(strtoupper($lang)) ?></h2>
    <?= $view->component('status-pill', ['label' => $view->t($section['enabled'] ? 'admin.lists.visible' : 'admin.users.inactive'), 'tone' => $section['enabled'] ? 'confirmed' : 'neutral', 'dot' => $section['enabled']]) ?>

  </div>
  <div class="fields-2 fields-2--sec">
    <?= $view->component('input', ['name' => 'label', 'label' => $view->t('admin.pages.label'), 'value' => $values['label'], 'disabled' => !$canEdit]) ?>

    <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.pages.page_title'), 'value' => $values['title'], 'disabled' => !$canEdit]) ?>

    <?= $view->component('input', ['name' => 'highlight', 'label' => $view->t('admin.pages.highlight'), 'value' => $values['highlight'], 'hint' => $view->t('admin.pages.highlight_hint'), 'disabled' => !$canEdit]) ?>

  </div>
  <?= $view->component('textarea', ['name' => 'intro', 'id' => 'section-intro', 'label' => $view->t('admin.pages.intro'), 'value' => $values['intro'], 'rows' => 3, 'maxlength' => 2000, 'disabled' => !$canEdit]) ?>

<?php if ($type === 'hero' || $type === 'about'): ?>
  <h3 class="h4 sec-sub"><?= e($view->t('admin.sections.images')) ?></h3>
  <p class="muted"><?= e($view->t('admin.sections.images_hint')) ?></p>
  <div class="fields-2">
    <?= $view->component('select', ['name' => 'media', 'id' => 'section-media', 'label' => $view->t($type === 'hero' ? 'admin.sections.hero_image' : 'admin.sections.image_main'), 'value' => $section['media'], 'options' => $images, 'disabled' => !$canEdit, 'attrs' => ['data-media-preview' => 'image']]) ?>

<?php if ($type === 'about'): ?>
    <?= $view->component('select', ['name' => 'media2', 'id' => 'section-media2', 'label' => $view->t('admin.sections.image_second'), 'value' => $section['media2'], 'options' => $images, 'disabled' => !$canEdit, 'attrs' => ['data-media-preview' => 'image']]) ?>

<?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($type === 'about'): ?>
  <h3 class="h4 sec-sub"><?= e($view->t('admin.sections.badge')) ?></h3>
  <div class="fields-2">
    <?= $view->component('input', ['name' => 'badge_value', 'label' => $view->t('admin.sections.badge_value'), 'value' => $str($extra['badge_value'] ?? null), 'hint' => $view->t('admin.sections.badge_value_hint'), 'disabled' => !$canEdit]) ?>

    <?= $view->component('input', ['name' => 'badge_label', 'label' => $view->t('admin.sections.badge_label'), 'value' => $str($extra['badge_label'] ?? null), 'disabled' => !$canEdit]) ?>

  </div>
  <h3 class="h4 sec-sub"><?= e($view->t('admin.sections.points')) ?></h3>
  <p class="muted"><?= e($view->t('admin.sections.points_hint')) ?></p>
<?php for ($i = 0; $i < 3; $i++): $point = is_array($points[$i] ?? null) ? $points[$i] : []; ?>
  <fieldset class="point-row">
    <legend><?= e($view->t('admin.sections.point', ['n' => $i + 1])) ?></legend>
    <div class="fields-2">
      <?= $view->component('select', ['name' => 'point_icon_' . $i, 'id' => 'point-icon-' . $i, 'label' => $view->t('admin.expertise.icon'), 'value' => $str($point['icon'] ?? null) ?: 'check', 'options' => $pointIcons, 'disabled' => !$canEdit]) ?>

      <?= $view->component('input', ['name' => 'point_title_' . $i, 'label' => $view->t('admin.sections.point_title'), 'value' => $str($point['title'] ?? null), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

    </div>
    <?= $view->component('input', ['name' => 'point_text_' . $i, 'label' => $view->t('admin.sections.point_text'), 'value' => $str($point['text'] ?? null), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

  </fieldset>
<?php endfor; ?>
<?php endif; ?>
<?php if ($type === 'map'): ?>
  <h3 class="h4 sec-sub"><?= e($view->t('admin.sections.map_regions')) ?></h3>
  <p class="muted"><?= e($view->t('admin.sections.map_hint')) ?></p>
  <?= $view->component('select', ['name' => 'main', 'id' => 'section-main', 'label' => $view->t('admin.sections.map_main'), 'value' => $str($extra['main'] ?? null), 'options' => $regions, 'disabled' => !$canEdit]) ?>

  <div class="fields-2">
<?php foreach (array_slice($regions, 1) as $region): ?>
    <?= $view->component('input', ['name' => 'note_' . $region['value'], 'label' => $region['label'], 'value' => $str($notes[$region['value']] ?? null), 'placeholder' => $view->t('admin.sections.map_note_placeholder'), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="actions">
    <?= $view->component('button', ['label' => $view->t('admin.common.back'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/pages/' . $section['page_id'] . '?lang=' . $lang]) ?>
<?php if ($canEdit): ?>
    <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

<?php endif; ?>
  </div>
</form>
