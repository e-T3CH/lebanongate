<?php
/**
 * @var \Gate\Core\View $view
 * @var string|null $error
 * @var string $action
 * @var string $cancelAction
 */
?>
<div class="auth__intro">
  <h1 class="atop__title"><?= e($view->t('admin.two_factor.title')) ?></h1>
  <p class="muted"><?= e($view->t('admin.two_factor.subtitle')) ?></p>
</div>
<?php if ($error !== null): ?>
<?= $view->component('notice', ['title' => $error, 'role' => 'alert']) ?>

<?php endif; ?>
<form class="auth__form" method="post" action="<?= e_url($action) ?>" novalidate>
  <?= $view->csrfField() ?>
  <?= $view->component('input', ['name' => 'code', 'label' => $view->t('admin.two_factor.code'), 'autocomplete' => 'one-time-code', 'inputmode' => 'text', 'required' => true, 'autofocus' => true, 'hint' => $view->t('admin.two_factor.recovery_hint')]) ?>
  <?= $view->component('button', ['label' => $view->t('admin.two_factor.verify'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right', 'class' => 'auth__submit']) ?>

</form>
<form method="post" action="<?= e_url($cancelAction) ?>">
  <?= $view->csrfField() ?>
  <?= $view->component('button', ['label' => $view->t('admin.two_factor.cancel'), 'variant' => 'admin-secondary', 'type' => 'submit', 'class' => 'auth__submit']) ?>

</form>
