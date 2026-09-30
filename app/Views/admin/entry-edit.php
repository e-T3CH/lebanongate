<?php
/**
 * One project / article / publication / album: language tabs; the texts of the chosen language with its publish
 * switch; the settings of the type (shared by all languages); the photo gallery; delete.
 *
 * @var \Gate\Core\View $view
 * @var string $type
 * @var array<string, mixed> $entry id + settings (strings), is_enabled, is_featured
 * @var string $lang
 * @var array<string, string> $values
 * @var bool $published
 * @var bool $isNewTranslation
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var string $base
 * @var string|null $publicUrl
 * @var array<string, list<array{value: string, label: string}>> $options
 * @var list<array{id: int, url: string, original_name: string, width: int, height: int}> $gallery
 * @var list<array{id: int, url: string, original_name: string, width: int, height: int}> $images
 * @var list<int> $galleryIds
 * @var array<string, string> $errors
 * @var bool $canEdit
 * @var string $adminPath
 */

use Gate\Content\EntryTypes;

$uses = static fn (string $feature): bool => EntryTypes::uses($type, $feature);
$s = static fn (string $key): string => is_scalar($entry[$key] ?? null) ? (string) $entry[$key] : '';
$id = (int) $entry['id'];
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<form class="flex gap-20 agrow" method="post" action="<?= e_url($base . '/' . $id) ?>" novalidate>
  <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
  <div class="acard agrow sec-card">
    <div class="sec-card__head">
      <h2 class="h3"><?= e($view->t('admin.entries.texts')) ?> · <?= e(strtoupper($lang)) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t($isNewTranslation ? 'admin.content.missing' : ($published ? 'admin.content.published' : 'admin.content.draft')), 'tone' => $isNewTranslation ? 'neutral' : ($published ? 'confirmed' : 'diagnosis'), 'dot' => $published && !$isNewTranslation]) ?>

    </div>
<?php if ($isNewTranslation): ?>
    <?= $view->component('notice', ['title' => $view->t('admin.entries.new_translation_title'), 'text' => $view->t('admin.entries.new_translation_text')]) ?>

<?php endif; ?>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.entries.title_field'), 'value' => $values['title'], 'error' => $errors['title'] ?? null, 'required' => true, 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

      <?= $view->component('input', ['name' => 'slug', 'label' => $view->t('admin.pages.slug'), 'value' => $values['slug'], 'error' => $errors['slug'] ?? null, 'hint' => $view->t('admin.entries.slug_hint'), 'disabled' => !$canEdit]) ?>

    </div>
    <?= $view->component('textarea', ['name' => 'summary', 'id' => 'entry-summary', 'label' => $view->t('admin.entries.summary'), 'value' => $values['summary'], 'rows' => 3, 'maxlength' => 600, 'hint' => $view->t('admin.entries.summary_hint'), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

<?php if ($type !== 'album'): ?>
    <?= $view->component('textarea', ['name' => 'body', 'id' => 'entry-body', 'label' => $view->t('admin.pages.body'), 'value' => $values['body'], 'rows' => 14, 'hint' => $view->t('admin.pages.body_hint'), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

<?php endif; ?>
<?php if ($uses('location')): ?>
    <?= $view->component('input', ['name' => 'location', 'label' => $view->t('site.entries.location'), 'value' => $values['location'], 'hint' => $view->t('admin.entries.location_hint'), 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

<?php endif; ?>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'meta_title', 'label' => $view->t('admin.pages.meta_title'), 'value' => $values['meta_title'], 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

      <?= $view->component('input', ['name' => 'meta_description', 'label' => $view->t('admin.pages.meta_description'), 'value' => $values['meta_description'], 'maxlength' => 320, 'disabled' => !$canEdit, 'attrs' => ['dir' => 'auto']]) ?>

    </div>
    <?= $view->component('setting-row', ['name' => $view->t('admin.content.publish'), 'description' => $view->t('admin.entries.publish_desc', ['lang' => strtoupper($lang)]), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'is_published', 'label' => $view->t('admin.content.publish'), 'checked' => $published, 'disabled' => !$canEdit])]) ?>

<?php if ($canEdit): ?>
    <div class="actions">
      <?php if ($publicUrl !== null): ?><?= $view->component('button', ['label' => $view->t('admin.entries.view_on_site'), 'variant' => 'admin-secondary', 'href' => $publicUrl, 'icon' => 'fa-solid fa-arrow-up-right-from-square', 'iconPosition' => 'start', 'attrs' => ['target' => '_blank', 'rel' => 'noopener']]) ?><?php endif; ?>

      <?= $view->component('button', ['label' => $view->t('admin.common.back'), 'variant' => 'admin-secondary', 'href' => $base]) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
<?php endif; ?>
  </div>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.entries.settings')) ?></h2><span class="muted"><?= e($view->t('admin.entries.settings_desc')) ?></span></div>
      <?= $view->component('setting-row', ['name' => $view->t('admin.entries.on_site'), 'description' => $view->t('admin.entries.on_site_desc'), 'control' => $view->component('toggle', ['name' => 'is_enabled', 'label' => $view->t('admin.entries.on_site'), 'checked' => (bool) $entry['is_enabled'], 'disabled' => !$canEdit])]) ?>

<?php if ($uses('featured')): ?>
      <?= $view->component('setting-row', ['name' => $view->t('admin.entries.featured'), 'description' => $view->t('admin.entries.featured_desc'), 'control' => $view->component('toggle', ['name' => 'is_featured', 'label' => $view->t('admin.entries.featured'), 'checked' => (bool) $entry['is_featured'], 'disabled' => !$canEdit])]) ?>

<?php endif; ?>
      <?= $view->component('input', ['name' => 'published_on', 'type' => 'date', 'label' => $view->t($type === 'project' ? 'admin.entries.date_project' : 'admin.entries.date'), 'value' => $s('published_on'), 'error' => $errors['published_on'] ?? null, 'required' => true, 'disabled' => !$canEdit]) ?>

<?php if ($options['statuses'] !== []): ?>
      <?= $view->component('select', ['name' => 'status', 'id' => 'entry-status', 'label' => $view->t($type === 'project' ? 'site.entries.status' : 'site.entries.kind'), 'value' => $s('status'), 'options' => $options['statuses'], 'disabled' => !$canEdit]) ?>

<?php endif; ?>
<?php if ($uses('expertise')): ?>
      <?= $view->component('select', ['name' => 'expertise_id', 'id' => 'entry-expertise', 'label' => $view->t('site.entries.sector'), 'value' => $s('expertise_id'), 'options' => $options['expertise'], 'disabled' => !$canEdit]) ?>

<?php endif; ?>
<?php if ($uses('region')): ?>
      <?= $view->component('select', ['name' => 'region', 'id' => 'entry-region', 'label' => $view->t('site.entries.region'), 'value' => $s('region'), 'options' => $options['regions'], 'disabled' => !$canEdit]) ?>

<?php endif; ?>
<?php if ($uses('dates')): ?>
      <div class="fields-2">
        <?= $view->component('input', ['name' => 'start_date', 'type' => 'date', 'label' => $view->t('admin.entries.start'), 'value' => $s('start_date'), 'error' => $errors['start_date'] ?? null, 'disabled' => !$canEdit]) ?>

        <?= $view->component('input', ['name' => 'end_date', 'type' => 'date', 'label' => $view->t('admin.entries.end'), 'value' => $s('end_date'), 'error' => $errors['end_date'] ?? null, 'disabled' => !$canEdit]) ?>

      </div>
<?php endif; ?>
<?php if ($uses('beneficiaries')): ?>
      <?= $view->component('input', ['name' => 'beneficiaries', 'label' => $view->t('site.entries.beneficiaries'), 'value' => $s('beneficiaries'), 'error' => $errors['beneficiaries'] ?? null, 'hint' => $view->t('admin.entries.beneficiaries_hint'), 'disabled' => !$canEdit]) ?>

<?php endif; ?>
<?php if ($uses('donors')): ?>
      <?= $view->component('input', ['name' => 'donors', 'label' => $view->t('site.entries.donors'), 'value' => $s('donors'), 'hint' => $view->t('admin.entries.donors_hint'), 'disabled' => !$canEdit]) ?>

<?php endif; ?>
      <?= $view->component('select', ['name' => 'cover_media_id', 'id' => 'entry-cover', 'label' => $view->t('admin.entries.cover'), 'value' => $s('cover_media_id'), 'options' => $options['images'], 'hint' => $view->t('admin.media.pick_hint'), 'disabled' => !$canEdit, 'attrs' => ['data-media-preview' => 'image']]) ?>

<?php if ($uses('file')): ?>
      <?= $view->component('select', ['name' => 'file_media_id', 'id' => 'entry-file', 'label' => $view->t('admin.entries.file'), 'value' => $s('file_media_id'), 'options' => $options['documents'], 'error' => $errors['file_media_id'] ?? null, 'hint' => $view->t('admin.entries.file_hint'), 'disabled' => !$canEdit]) ?>

<?php endif; ?>
<?php if ($uses('related')): ?>
      <?= $view->component('select', ['name' => 'related_id', 'id' => 'entry-related', 'label' => $view->t('site.entries.related'), 'value' => $s('related_id'), 'options' => $options['projects'], 'disabled' => !$canEdit]) ?>

<?php endif; ?>
    </div>
  </div>
</form>

<?php if ($uses('gallery')): ?>
<form class="acard sec-card" id="gallery" method="post" action="<?= e_url($base . '/' . $id . '/gallery') ?>">
  <?= $view->csrfField() ?>
  <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.entries.gallery_title')) ?></h2><span class="muted"><?= e($view->t('admin.entries.gallery_desc')) ?></span></div>
<?php if ($gallery !== []): ?>
  <ul class="sortable sortable--wide" data-sortable aria-label="<?= e_attr($view->t('admin.entries.gallery_title')) ?>">
<?php foreach ($gallery as $photo): ?>
    <li class="sortable__item" data-id="<?= e_attr((string) $photo['id']) ?>">
      <input type="hidden" name="order[]" value="<?= e_attr((string) $photo['id']) ?>">
      <span class="sortable__handle" draggable="true"><?= $view->component('icon', ['icon' => 'fa-solid fa-grip-vertical', 'size' => '14']) ?></span>
      <img class="thumb" src="<?= e_url($photo['url']) ?>" alt="" width="64" height="48" loading="lazy">
      <span class="sortable__label"><?= e($photo['original_name']) ?></span>
      <span class="sortable__moves">
        <?= $view->component('button', ['label' => $view->t('admin.content.move_up'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-up', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'attrs' => ['data-move' => 'up']]) ?>

        <?= $view->component('button', ['label' => $view->t('admin.content.move_down'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-down', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'attrs' => ['data-move' => 'down']]) ?>

      </span>
      <?= $view->component('toggle', ['name' => 'keep[]', 'value' => (string) $photo['id'], 'uncheckedValue' => null, 'label' => $view->t('admin.entries.keep_photo') . ': ' . $photo['original_name'], 'checked' => true, 'disabled' => !$canEdit]) ?>

    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <?= $view->component('empty-state', ['title' => $view->t('admin.entries.gallery_empty'), 'text' => $view->t('admin.entries.gallery_empty_text'), 'icon' => 'fa-regular fa-images']) ?>

<?php endif; ?>
<?php $available = array_values(array_filter($images, static fn (array $img): bool => !in_array($img['id'], $galleryIds, true))); ?>
<?php if ($available !== [] && $canEdit): ?>
  <fieldset class="pick-grid">
    <legend class="h4"><?= e($view->t('admin.entries.gallery_add')) ?></legend>
<?php foreach ($available as $img): ?>
    <label class="pick"><input type="checkbox" name="add[]" value="<?= e_attr((string) $img['id']) ?>"><img src="<?= e_url($img['url']) ?>" alt="" loading="lazy"><span><?= e($img['original_name']) ?></span></label>
<?php endforeach; ?>
  </fieldset>
<?php elseif ($images === []): ?>
  <p class="muted"><?= e($view->t('admin.entries.gallery_upload_first')) ?> <a class="link-sm" href="<?= e_url($adminPath . '/media') ?>"><?= e($view->t('admin.nav.media')) ?></a></p>
<?php endif; ?>
<?php if ($canEdit): ?>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.entries.gallery_save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
</form>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="acard panel-fields danger-zone">
  <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.content.remove')) ?></h2><span class="muted"><?= e($view->t('admin.entries.delete_desc')) ?></span></div>
  <?= $view->component('row-actions', ['actions' => [
      ['action' => $base . '/' . $id . '/delete', 'label' => $view->t('admin.entries.delete'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.entries.delete_confirm'), 'confirmTitle' => $view->t('admin.content.remove')],
  ]]) ?>

</div>
<?php endif; ?>
