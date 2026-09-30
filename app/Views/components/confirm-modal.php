<?php
/**
 * Confirm modal: replaces the browser's confirm(). Rendered once per layout; ui.js opens it for elements with
 * data-confirm (forms, submit buttons, links) and through BM.confirm({...}) → Promise<boolean>.
 * Native <dialog>: focus stays inside, Esc cancels, focus returns to the trigger. Without JavaScript the action
 * runs directly (the server validates it as usual).
 * Parameters: Components::SPECS['confirm-modal'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{id: string, title: ?string, message: ?string, confirmLabel: ?string, cancelLabel: ?string, tone: string, open: bool} $p
 */

$id = $p['id'];
?>
<dialog class="modal" id="<?= e_attr($id) ?>" aria-labelledby="<?= e_attr($id) ?>-title" aria-describedby="<?= e_attr($id) ?>-text" data-confirm-modal data-default-title="<?= e_attr($view->t('ui.confirm.title')) ?>" data-default-message="<?= e_attr($view->t('ui.confirm.message')) ?>" data-default-ok="<?= e_attr($view->t('ui.confirm.ok')) ?>" data-default-cancel="<?= e_attr($view->t('ui.confirm.cancel')) ?>"<?= $p['open'] ? ' open' : '' ?>>
  <form class="modal__box" method="dialog">
    <span class="modal__icon<?= $p['tone'] === 'danger' ? ' modal__icon--danger' : '' ?>" data-confirm-icon><?= $view->component('icon', ['icon' => 'fa-solid fa-triangle-exclamation', 'size' => '18']) ?></span>
    <div class="modal__col">
      <h2 class="h3" id="<?= e_attr($id) ?>-title" data-confirm-title><?= e($p['title'] ?? $view->t('ui.confirm.title')) ?></h2>
      <p class="muted modal__text" id="<?= e_attr($id) ?>-text" data-confirm-text><?= e($p['message'] ?? $view->t('ui.confirm.message')) ?></p>
    </div>
    <div class="modal__actions">
      <?= $view->component('button', ['label' => $p['cancelLabel'] ?? $view->t('ui.confirm.cancel'), 'variant' => 'admin-secondary', 'type' => 'submit', 'value' => 'cancel', 'attrs' => ['data-confirm-cancel' => true]]) ?>

      <?= $view->component('button', ['label' => $p['confirmLabel'] ?? $view->t('ui.confirm.ok'), 'variant' => $p['tone'] === 'danger' ? 'admin-danger' : 'admin-primary', 'type' => 'submit', 'value' => 'confirm', 'attrs' => ['data-confirm-ok' => true]]) ?>

    </div>
  </form>
</dialog>
