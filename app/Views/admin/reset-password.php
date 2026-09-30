<?php
/**
 * Choosing a new password from the link in the reset email. An expired, used or unknown link shows a short
 * explanation instead of the form (HTTP 410).
 *
 * @var \BMMatic\Core\View $view
 * @var bool $valid
 * @var array<string, string> $errors
 * @var string $action
 * @var string $forgotHref
 * @var int $minLength
 */
?>
<?php if (!$valid): ?>
<div class="auth__intro">
  <h1 class="atop__title"><?= e($view->t('admin.reset.expired_title')) ?></h1>
  <p class="muted"><?= e($view->t('admin.reset.expired_text')) ?></p>
</div>
<?= $view->component('button', ['label' => $view->t('admin.reset.request_again'), 'variant' => 'admin-secondary', 'href' => $forgotHref, 'class' => 'auth__submit']) ?>

<?php else: ?>
<div class="auth__intro">
  <h1 class="atop__title"><?= e($view->t('admin.reset.title')) ?></h1>
  <p class="muted"><?= e($view->t('admin.reset.subtitle', ['min' => $minLength])) ?></p>
</div>
<form class="auth__form" method="post" action="<?= e_url($action) ?>" novalidate>
  <?= $view->csrfField() ?>
  <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('admin.reset.password'), 'autocomplete' => 'new-password', 'error' => $errors['password'] ?? null, 'required' => true, 'autofocus' => true]) ?>

  <?= $view->component('input', ['name' => 'password_confirm', 'type' => 'password', 'label' => $view->t('admin.reset.password_confirm'), 'autocomplete' => 'new-password', 'error' => $errors['password_confirm'] ?? null, 'required' => true]) ?>

  <?= $view->component('button', ['label' => $view->t('admin.reset.submit'), 'variant' => 'admin-primary', 'type' => 'submit', 'class' => 'auth__submit']) ?>

</form>
<?php endif; ?>
