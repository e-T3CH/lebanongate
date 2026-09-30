<?php
/**
 * Content → lists: transmission types, process steps, key figures and partners. One screen per list: the rows of the
 * chosen language with their order, visibility and one Save button.
 *
 * @var \Gate\Core\View $view
 * @var string $type
 * @var list<string> $fields
 * @var list<array{id: int, enabled: bool, values: array<string, string>}> $rows
 * @var string $lang
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var bool $canEdit
 * @var bool|null $partners
 * @var string $adminPath
 */
$action = $adminPath . '/content/' . $type . ($partners === true ? '/partners' : '');
$label = static fn (string $field): string => $view->t('admin.lists.' . $field);
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<div class="flex gap-20 agrow">
  <form class="acard agrow col" method="post" action="<?= e_url($action) ?>" novalidate>
    <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.lists.' . $type . '_title')) ?> · <?= e(strtoupper($lang)) ?></h2></div>
    <ul class="sortable sortable--rows" data-sortable aria-label="<?= e_attr($view->t('admin.lists.' . $type . '_title')) ?>">
<?php foreach ($rows as $row): ?>
      <li class="sortable__item sortable__item--form" data-id="<?= e_attr((string) $row['id']) ?>">
        <input type="hidden" name="order[]" value="<?= e_attr((string) $row['id']) ?>">
        <span class="sortable__handle" draggable="true"><?= $view->component('icon', ['icon' => 'fa-solid fa-layer-group', 'size' => '14']) ?></span>
        <div class="sortable__fields">
<?php foreach ($fields as $field): ?>
          <?= $view->component('input', ['name' => $field . '_' . $row['id'], 'label' => $label($field), 'value' => $row['values'][$field] ?? '', 'disabled' => !$canEdit]) ?>

<?php endforeach; ?>
        </div>
        <span class="sortable__moves">
          <?= $view->component('button', ['label' => $view->t('admin.content.move_up'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-up', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_up') . ' #' . $row['id'], 'attrs' => ['data-move' => 'up']]) ?>

          <?= $view->component('button', ['label' => $view->t('admin.content.move_down'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-down', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_down') . ' #' . $row['id'], 'attrs' => ['data-move' => 'down']]) ?>

        </span>
        <?= $view->component('toggle', ['name' => 'enabled[]', 'value' => (string) $row['id'], 'uncheckedValue' => null, 'label' => $view->t('admin.lists.visible') . ' #' . $row['id'], 'checked' => $row['enabled'], 'disabled' => !$canEdit]) ?>

      </li>
<?php endforeach; ?>
    </ul>
<?php if ($rows === []): ?>
    <?= $view->component('empty-state', ['title' => $view->t('admin.lists.' . $type . '_title'), 'text' => $view->t('admin.content.add'), 'icon' => 'fa-solid fa-layer-group']) ?>

<?php endif; ?>
<?php if ($canEdit && $rows !== []): ?>
    <div class="actions"><span class="muted"><?= e($view->t('admin.content.drag_hint')) ?></span><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
  </form>
<?php if ($canEdit): ?>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/content/' . $type . '/new') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.content.add')) ?></h2><span class="muted"><?= e($view->t('admin.lists.subtitle')) ?></span></div>
<?php if ($partners === true): ?>
      <?= $view->component('input', ['name' => 'name', 'label' => $view->t('admin.lists.name'), 'required' => true]) ?>

<?php endif; ?>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.content.add'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
    </form>
<?php if ($rows !== []): ?>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.content.remove')) ?></h2><span class="muted"><?= e($view->t('admin.content.remove_confirm')) ?></span></div>
      <?= $view->component('row-actions', ['actions' => array_map(static fn (array $row): array => [
          'action' => $adminPath . '/content/' . $type . '/' . $row['id'] . '/delete',
          'label' => '#' . $row['id'] . ' ' . mb_strimwidth(reset($row['values']) ?: '', 0, 20, '…'),
          'icon' => 'fa-solid fa-xmark',
          'tone' => 'danger',
          'confirm' => $view->t('admin.content.remove_confirm'),
          'confirmTitle' => $view->t('admin.content.remove'),
      ], $rows)]) ?>

    </div>
<?php endif; ?>
  </div>
<?php endif; ?>
</div>
