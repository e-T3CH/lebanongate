<?php
/**
 * Content → Media: the uploaded images and PDF documents with their alt text (or title) per language, where they are
 * used, replace and delete.
 *
 * @var \Gate\Core\View $view
 * @var list<array<string, mixed>> $items
 * @var list<string> $languages
 * @var string $search
 * @var bool $canManage
 * @var int $maxMb
 * @var int $maxPdfMb
 * @var string $kind
 * @var list<array{label: string, href: string, active: bool}> $kinds
 * @var mixed $error
 * @var string $adminPath
 */
$str = static fn (array $item, string $key): string => is_scalar($item[$key] ?? null) ? (string) $item[$key] : '';
$accept = 'image/png,image/jpeg,image/webp,application/pdf,.pdf';
?>
<?= $view->component('tabs', ['label' => $view->t('admin.media.kind'), 'items' => $kinds]) ?>

<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head">
      <h2 class="h3"><?= e($view->t('admin.media.card_title')) ?></h2>
      <form class="media-search" method="get" action="<?= e_url($adminPath . '/media') ?>">
<?php if ($kind !== ''): ?>
        <input type="hidden" name="kind" value="<?= e_attr($kind) ?>">
<?php endif; ?>
        <?= $view->component('input', ['name' => 'q', 'type' => 'search', 'label' => $view->t('admin.common.search'), 'value' => $search, 'placeholder' => $view->t('admin.media.search_placeholder')]) ?>

        <?= $view->component('button', ['label' => $view->t('admin.common.search'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start', 'iconOnly' => true, 'class' => 'btn-row btn-icon']) ?>

      </form>
    </div>
<?php if ($items === []): ?>
    <?= $view->component('empty-state', ['title' => $view->t('admin.media.empty_title'), 'text' => $view->t('admin.media.empty_text'), 'icon' => 'fa-regular fa-image']) ?>

<?php else: ?>
    <ul class="media-grid">
<?php foreach ($items as $item): ?>
<?php
$id = (int) $item['id'];
/** @var list<string> $usage */
$usage = is_array($item['usage'] ?? null) ? $item['usage'] : [];
/** @var list<string> $missing */
$missing = is_array($item['alt_missing'] ?? null) ? $item['alt_missing'] : [];
/** @var array<string, string> $alt */
$alt = is_array($item['alt'] ?? null) ? $item['alt'] : [];
$isPdf = $str($item, 'kind') === 'document';
?>
      <li class="media-item">
        <a class="media-item__thumb<?= $isPdf ? ' media-item__thumb--doc' : '' ?>" href="<?= e_url($str($item, 'url')) ?>" target="_blank" rel="noopener">
<?php if ($isPdf): ?>
          <?= $view->component('icon', ['icon' => 'fa-regular fa-file-pdf', 'size' => '34']) ?><span class="visually-hidden"><?= e($str($item, 'original_name')) ?></span>
<?php else: ?>
          <img src="<?= e_url($str($item, 'url')) ?>" alt="<?= e_attr($alt[$languages[0] ?? 'en'] ?? $str($item, 'original_name')) ?>" width="<?= (int) $item['width'] ?>" height="<?= (int) $item['height'] ?>" loading="lazy" decoding="async">
<?php endif; ?>
        </a>
        <div class="media-item__body">
          <span class="media-item__name"><?= e($str($item, 'original_name')) ?></span>
<?php if ($isPdf): ?>
          <span class="muted">PDF · <?= e($view->t((int) $item['pages'] === 1 ? 'admin.media.page_one' : 'admin.media.pages', ['count' => (int) $item['pages']])) ?> · <?= e($str($item, 'size_kb')) ?> · <?= e($str($item, 'uploaded')) ?></span>
<?php else: ?>
          <span class="muted"><?= (int) $item['width'] ?>×<?= (int) $item['height'] ?> · <?= e($str($item, 'size_kb')) ?> · <?= e($str($item, 'uploaded')) ?></span>
<?php endif; ?>
          <span class="pill-row">
<?php if ($usage === []): ?>
            <?= $view->component('status-pill', ['label' => $view->t('admin.media.unused')]) ?>

<?php else: ?>
<?php foreach ($usage as $label): ?>
            <?= $view->component('status-pill', ['label' => $label, 'tone' => 'confirmed']) ?>

<?php endforeach; ?>
<?php endif; ?>
<?php if ($missing !== [] && !$isPdf): ?>
            <?= $view->component('status-pill', ['label' => $view->t('admin.media.alt_missing', ['langs' => strtoupper(implode(', ', $missing))]), 'tone' => 'diagnosis']) ?>

<?php endif; ?>
          </span>
<?php if ($canManage): ?>
          <form class="media-item__alt" method="post" action="<?= e_url($adminPath . '/media/' . $id . '/alt') ?>" novalidate>
            <?= $view->csrfField() ?>
<?php foreach ($languages as $lang): ?>
            <?= $view->component('input', ['name' => 'alt_' . $lang, 'id' => 'alt-' . $lang . '-' . $id, 'label' => $view->t($isPdf ? 'admin.media.doc_title' : 'admin.media.alt') . ' ' . strtoupper($lang), 'value' => $alt[$lang] ?? '', 'maxlength' => 200]) ?>

<?php endforeach; ?>
            <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-secondary', 'type' => 'submit', 'class' => 'btn-row']) ?></div>
          </form>
          <div class="media-item__actions">
            <form method="post" action="<?= e_url($adminPath . '/media/' . $id . '/replace') ?>" enctype="multipart/form-data">
              <?= $view->csrfField() ?>
              <label class="file-field">
                <span class="file-field__label"><?= e($view->t('admin.media.replace')) ?></span>
                <input type="file" name="file" accept="<?= e_attr($isPdf ? 'application/pdf,.pdf' : 'image/png,image/jpeg,image/webp') ?>" required>
              </label>
              <?= $view->component('button', ['label' => $view->t('admin.media.replace'), 'variant' => 'admin-secondary', 'type' => 'submit', 'class' => 'btn-row']) ?>

            </form>
            <?= $view->component('row-actions', ['actions' => [[
                'action' => $adminPath . '/media/' . $id . '/delete',
                'label' => $view->t('admin.content.remove'),
                'icon' => 'fa-solid fa-xmark',
                'tone' => 'danger',
                'confirm' => $view->t($usage === [] ? 'admin.media.delete_confirm' : 'admin.media.delete_in_use', ['where' => implode(', ', $usage)]),
                'confirmTitle' => $view->t('admin.content.remove'),
            ]]]) ?>

          </div>
<?php endif; ?>
        </div>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
<?php if ($canManage): ?>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/media/upload') ?>" enctype="multipart/form-data">
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.media.upload_title')) ?></h2><span class="muted"><?= e($view->t('admin.media.upload_desc', ['mb' => $maxMb, 'pdf' => $maxPdfMb])) ?></span></div>
<?php if (is_string($error)): ?>
      <?= $view->component('notice', ['title' => $error, 'role' => 'alert']) ?>

<?php endif; ?>
      <label class="file-field">
        <span class="file-field__label"><?= e($view->t('admin.media.file')) ?></span>
        <input type="file" name="file" accept="<?= e_attr($accept) ?>" required>
      </label>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.media.upload'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-up-from-bracket', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
<?php endif; ?>
</div>
