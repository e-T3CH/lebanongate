<?php
/**
 * @var \Gate\Core\View $view
 * @var string|null $error
 * @var string|null $notice
 * @var string $email
 * @var string $action
 * @var string $forgotHref
 */
?>
<div class="auth__intro">
  <h1 class="atop__title"><?= e($view->t('admin.login.title')) ?></h1>
  <p class="muted"><?= e($view->t('admin.login.subtitle')) ?></p>
</div>
<?php if ($notice !== null): ?>
<?= $view->component('status-pill', ['label' => $notice, 'tone' => 'new', 'class' => 'auth__notice', 'attrs' => ['role' => 'status']]) ?>

<?php endif; ?>
<?php if ($error !== null): ?>
<?= $view->component('notice', ['title' => $error, 'role' => 'alert']) ?>

<?php endif; ?>
<form class="auth__form" method="post" action="<?= e_url($action) ?>" novalidate>
  <?= $view->csrfField() ?>
  <?= $view->component('input', ['name' => 'email', 'type' => 'email', 'label' => $view->t('admin.login.email'), 'value' => $email, 'autocomplete' => 'username', 'required' => true, 'autofocus' => $email === '']) ?>
  <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('admin.login.password'), 'autocomplete' => 'current-password', 'required' => true, 'autofocus' => $email !== '']) ?>
  <?= $view->component('button', ['label' => $view->t('admin.login.submit'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right', 'class' => 'auth__submit']) ?>

</form>
<a class="link-sm auth__link" href="<?= e_url($forgotHref) ?>"><?= e($view->t('admin.login.forgot')) ?></a>
