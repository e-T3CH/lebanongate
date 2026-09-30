<?php
/**
 * Content → Services: order, visibility and the translation state per language, plus a card to add one.
 *
 * @var \BMMatic\Core\View $view
 * @var list<array{id: int, key: string, icon: string, title: string, enabled: bool, in_menu: bool, states: array<string, string>}> $services
 * @var list<string> $languages
 * @var bool $canEdit
 * @var array<string, string> $errors
 * @var string $adminPath
 */
?>
<div class="flex gap-20 agrow">
  <form class="acard agrow col" method="post" action="<?= e_url($adminPath . '/services/order') ?>">
    <?= $view->csrfField() ?>
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.services.card_title')) ?></h2></div>
    <ul class="sortable sortable--wide" data-sortable aria-label="<?= e_attr($view->t('admin.services.card_title')) ?>">
<?php foreach ($services as $service): ?>
      <li class="sortable__item" data-id="<?= e_attr((string) $service['id']) ?>">
        <input type="hidden" name="order[]" value="<?= e_attr((string) $service['id']) ?>">
        <span class="sortable__handle" draggable="true"><?= $view->component('icon', ['icon' => $service['icon'], 'size' => '16', 'class' => 'ic-accent']) ?></span>
        <span class="sortable__label">
          <a class="link-sm" href="<?= e_url($adminPath . '/services/' . $service['id']) ?>"><?= e($service['title']) ?></a>
          <span class="muted sortable__sub"><?= e($service['key']) ?></span>
        </span>
        <span class="pill-row">
<?php foreach ($languages as $lang): ?>
          <?php $state = $service['states'][$lang] ?? 'missing'; ?>
          <?= $view->component('status-pill', ['label' => strtoupper($lang), 'tone' => $state === 'published' ? 'confirmed' : ($state === 'draft' ? 'diagnosis' : 'neutral'), 'attrs' => ['title' => $view->t('admin.content.' . $state)]]) ?>

<?php endforeach; ?>
        </span>
        <span class="sortable__moves">
          <?= $view->component('button', ['label' => $view->t('admin.content.move_up'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-up', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_up') . ': ' . $service['title'], 'attrs' => ['data-move' => 'up']]) ?>

          <?= $view->component('button', ['label' => $view->t('admin.content.move_down'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-chevron-down', 'iconPosition' => 'start', 'class' => 'btn-row btn-icon', 'iconOnly' => true, 'ariaLabel' => $view->t('admin.content.move_down') . ': ' . $service['title'], 'attrs' => ['data-move' => 'down']]) ?>

        </span>
        <?= $view->component('toggle', ['name' => 'enabled[]', 'value' => (string) $service['id'], 'uncheckedValue' => null, 'label' => $view->t('admin.services.enabled') . ': ' . $service['title'], 'checked' => $service['enabled'], 'disabled' => !$canEdit]) ?>

      </li>
<?php endforeach; ?>
    </ul>
<?php if ($services === []): ?>
    <?= $view->component('empty-state', ['title' => $view->t('admin.services.card_title'), 'text' => $view->t('admin.services.add_desc'), 'icon' => 'fa-solid fa-wrench']) ?>

<?php endif; ?>
<?php if ($canEdit && $services !== []): ?>
    <div class="actions"><span class="muted"><?= e($view->t('admin.content.drag_hint')) ?></span><?= $view->component('button', ['label' => $view->t('admin.pages.save_order'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
  </form>
<?php if ($canEdit): ?>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/services/new') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.services.add_title')) ?></h2><span class="muted"><?= e($view->t('admin.services.add_desc')) ?></span></div>
      <?= $view->component('input', ['name' => 'key', 'label' => $view->t('admin.services.key'), 'error' => $errors['key'] ?? null, 'required' => true]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.content.add'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
    </form>
  </div>
<?php endif; ?>
</div>
