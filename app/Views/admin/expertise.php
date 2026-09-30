<?php
/**
 * Content → Areas of expertise: order, visibility and the translation state per language, plus a card to add one.
 *
 * @var \Gate\Core\View $view
 * @var list<array{id: int, key: string, icon: string, title: string, enabled: bool, states: array<string, string>}> $areas
 * @var list<string> $languages
 * @var bool $canEdit
 * @var array<string, string> $errors
 * @var string $adminPath
 */
?>
<div class="flex gap-20 agrow">
  <form class="acard agrow col" method="post" action="<?= e_url($adminPath . '/expertise/order') ?>">
    <?= $view->csrfField() ?>
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.expertise.card_title')) ?></h2></div>
    <ul class="sortable sortable--wide" data-sortable aria-label="<?= e_attr($view->t('admin.expertise.card_title')) ?>">
<?php foreach ($areas as $area): ?>
      <li class="sortable__item" data-id="<?= e_attr((string) $area['id']) ?>">
        <input type="hidden" name="order[]" value="<?= e_attr((string) $area['id']) ?>">
        <span class="sortable__handle" draggable="true"><?= $view->component('icon', ['icon' => 'fa-solid fa-grip-vertical', 'size' => '14']) ?></span>
        <span class="sortable__label">
          <a class="link-sm" href="<?= e_url($adminPath . '/expertise/' . $area['id']) ?>"><?= e($area['title']) ?></a>
          <span class="muted sortable__sub"><?= e($area['key']) ?></span>
        </span>
        <span class="pill-row">
<?php foreach ($languages as $lang): ?>
          <?php $state = $area['states'][$lang] ?? 'missing'; ?>
          <?= $view->component('status-pill', ['label' => strtoupper($lang), 'tone' => $state === 'published' ? 'confirmed' : ($state === 'draft' ? 'diagnosis' : 'neutral'), 'attrs' => ['title' => $view->t('admin.content.' . $state)]]) ?>

<?php endforeach; ?>
        </span>
        <span class="sortable__moves">
          <?= $view->component('button', ['label' => $view->t('admin.content.move_up'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-up', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_up') . ': ' . $area['title'], 'attrs' => ['data-move' => 'up']]) ?>

          <?= $view->component('button', ['label' => $view->t('admin.content.move_down'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-down', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_down') . ': ' . $area['title'], 'attrs' => ['data-move' => 'down']]) ?>

        </span>
        <?= $view->component('toggle', ['name' => 'enabled[]', 'value' => (string) $area['id'], 'uncheckedValue' => null, 'label' => $view->t('admin.expertise.enabled') . ': ' . $area['title'], 'checked' => $area['enabled'], 'disabled' => !$canEdit]) ?>

      </li>
<?php endforeach; ?>
    </ul>
<?php if ($areas === []): ?>
    <?= $view->component('empty-state', ['title' => $view->t('admin.expertise.card_title'), 'text' => $view->t('admin.expertise.add_desc'), 'icon' => 'fa-solid fa-layer-group']) ?>

<?php endif; ?>
<?php if ($canEdit && $areas !== []): ?>
    <div class="actions"><span class="muted"><?= e($view->t('admin.content.drag_hint')) ?></span><?= $view->component('button', ['label' => $view->t('admin.pages.save_order'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
  </form>
<?php if ($canEdit): ?>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/expertise/new') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.expertise.add_title')) ?></h2><span class="muted"><?= e($view->t('admin.expertise.add_desc')) ?></span></div>
      <?= $view->component('input', ['name' => 'key', 'label' => $view->t('admin.expertise.key'), 'error' => $errors['key'] ?? null, 'required' => true]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.content.add'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
<?php endif; ?>
</div>
