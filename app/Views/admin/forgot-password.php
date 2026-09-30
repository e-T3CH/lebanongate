<?php
/**
 * "Forgot your password?": asks for the email address and always gives the same answer afterwards, so the form
 * does not reveal which addresses have an account. Same card as the sign-in screen.
 *
 * @var \Gate\Core\View $view
 * @var bool $sent
 * @var string $action
 * @var string $loginHref
 */
?>
<div class="auth__intro">
  <h1 class="atop__title"><?= e($view->t('admin.reset.request_title')) ?></h1>
  <p class="muted"><?= e($view->t('admin.reset.request_subtitle')) ?></p>
</div>
<?php if ($sent): ?>
<?= $view->component('status-pill', ['label' => $view->t('admin.reset.sent'), 'tone' => 'new', 'class' => 'auth__notice', 'attrs' => ['role' => 'status']]) ?>

<?php endif; ?>
<form class="auth__form" method="post" action="<?= e_url($action) ?>" novalidate>
  <?= $view->csrfField() ?>
  <?= $view->component('input', ['name' => 'email', 'type' => 'email', 'label' => $view->t('admin.login.email'), 'autocomplete' => 'username', 'required' => true, 'autofocus' => !$sent]) ?>
  <?= $view->component('button', ['label' => $view->t('admin.reset.request_submit'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-paper-plane', 'class' => 'auth__submit']) ?>

</form>
<a class="link-sm auth__link" href="<?= e_url($loginHref) ?>"><?= e($view->t('admin.reset.back_to_login')) ?></a>
