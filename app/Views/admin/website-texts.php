<?php
/**
 * Content → Website texts: the fixed wording of the public site in one language, grouped by part of the site, with
 * the default language as a reference, a "Changed" mark and Reset per text.
 *
 * @var \Gate\Core\View $view
 * @var list<array{key: string, category: string, value: string, original: string, reference: string, custom: bool, long: bool}> $rows
 * @var string $lang
 * @var string $reference
 * @var bool $rtl
 * @var string $category
 * @var string $search
 * @var list<array{value: string, label: string}> $parts
 * @var int $changed
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var array<string, string> $old
 * @var array<string, string> $errors
 * @var bool $canEdit
 * @var string $adminPath
 */
$label = static function (string $key): string {
    $parts = array_slice(explode('.', $key), 1);
    return implode(' › ', array_map(static fn (string $p): string => ucfirst(str_replace('_', ' ', $p)), $parts));
};
$fieldId = static fn (string $key): string => 'tx-' . str_replace('.', '-', $key);
$current = '';
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<div class="acard agrow col texts">
  <form class="filters-row" method="get" action="<?= e_url($adminPath . '/website-texts') ?>">
    <input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
    <?= $view->component('select', ['name' => 'part', 'id' => 'texts-part', 'label' => $view->t('admin.texts.part'), 'value' => $category, 'options' => $parts]) ?>

    <?= $view->component('input', ['name' => 'q', 'type' => 'search', 'label' => $view->t('admin.texts.search'), 'value' => $search, 'placeholder' => $view->t('admin.texts.search_placeholder')]) ?>

    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.messages.filter'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start']) ?></div>
  </form>
  <p class="muted texts__intro"><?= e($view->t('admin.texts.intro', ['count' => count($rows), 'changed' => $changed])) ?></p>
<?php if ($rows === []): ?>
  <?= $view->component('empty-state', ['title' => $view->t('admin.texts.empty_title'), 'text' => $view->t('admin.texts.empty_text'), 'icon' => 'fa-solid fa-language']) ?>

<?php else: ?>
  <form method="post" action="<?= e_url($adminPath . '/website-texts') ?>" novalidate>
    <?= $view->csrfField() ?>
    <input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
    <input type="hidden" name="part" value="<?= e_attr($category) ?>">
    <input type="hidden" name="q" value="<?= e_attr($search) ?>">
<?php foreach ($rows as $row): ?>
<?php if ($row['category'] !== $current): $current = $row['category']; ?>
    <h2 class="h3 texts__part"><?= e($view->t('admin.texts.part_' . $current)) ?></h2>
<?php endif; ?>
<?php
$value = $old[$row['key']] ?? $row['value'];
$hints = [];
if ($lang !== $reference) {
    $hints[] = strtoupper($reference) . ': ' . $row['reference'];
}
if ($row['custom'] && $row['original'] !== '') {
    $hints[] = $view->t('admin.texts.original', ['text' => $row['original']]);
}
$placeholders = \Gate\Repositories\WebsiteTextRepository::placeholders($row['original'] !== '' ? $row['original'] : $row['reference']);
if ($placeholders !== []) {
    $hints[] = $view->t('admin.texts.keep', ['names' => implode(' ', $placeholders)]);
}
$field = [
    'name' => 'text[' . $row['key'] . ']',
    'id' => $fieldId($row['key']),
    'label' => $label($row['key']),
    'value' => $value,
    'hint' => $hints === [] ? null : implode(' · ', $hints),
    'error' => $errors[$row['key']] ?? null,
    'disabled' => !$canEdit,
    'attrs' => ['dir' => $rtl ? 'rtl' : 'ltr', 'lang' => $lang],
];
?>
    <div class="texts__row<?= $row['custom'] ? ' is-custom' : '' ?>">
      <div class="texts__field">
        <?= $row['long'] ? $view->component('textarea', $field + ['rows' => 3, 'maxlength' => 2000]) : $view->component('input', $field + ['maxlength' => 400]) ?>

      </div>
<?php if ($row['custom']): ?>
      <div class="texts__state">
        <?= $view->component('status-pill', ['label' => $view->t('admin.texts.changed'), 'tone' => 'diagnosis', 'dot' => true]) ?>

<?php if ($canEdit): ?>
        <button class="link-sm texts__reset" type="submit" name="reset" value="<?= e_attr($row['key']) ?>" formnovalidate data-confirm="<?= e_attr($view->t('admin.texts.reset_confirm')) ?>"><?= e($view->t('admin.texts.reset')) ?></button>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if ($canEdit): ?>
    <div class="actions texts__save"><span class="muted"><?= e($view->t('admin.texts.save_hint')) ?></span><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
  </form>
<?php endif; ?>
</div>
