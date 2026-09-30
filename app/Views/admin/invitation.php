<?php
/**
 * Accepting an invitation: the invited colleague chooses their own password. Same card as the sign-in screen.
 * An expired or unknown link shows a short explanation instead of the form (HTTP 410).
 *
 * @var \BMMatic\Core\View $view
 * @var array<string, mixed>|null $invitation
 * @var string $token
 * @var string $siteName
 * @var string $adminPath
 * @var array<string, string> $errors
 */
$email = is_array($invitation) && is_string($invitation['email'] ?? null) ? $invitation['email'] : '';
?>
<?= $view->partial('head', ['title' => $view->t('admin.invitation.title', ['site' => $siteName]), 'bundle' => 'admin', 'noindex' => true]) ?>
<body class="admin">
<main class="auth">
  <div class="acard auth__card">
<?php if ($invitation === null): ?>
    <div class="auth__intro">
      <h1 class="atop__title"><?= e($view->t('admin.invitation.expired_title')) ?></h1>
      <p class="muted"><?= e($view->t('admin.invitation.expired_text')) ?></p>
    </div>
    <?= $view->component('button', ['label' => $view->t('admin.login.submit'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/login', 'class' => 'auth__submit']) ?>

<?php else: ?>
    <div class="auth__intro">
      <h1 class="atop__title"><?= e($view->t('admin.invitation.title', ['site' => $siteName])) ?></h1>
      <p class="muted"><?= e($view->t('admin.invitation.subtitle', ['email' => $email])) ?></p>
    </div>
    <form class="auth__form" method="post" action="<?= e_url($adminPath . '/invitation/' . $token) ?>" novalidate>
      <?= $view->csrfField() ?>
      <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('admin.invitation.password'), 'autocomplete' => 'new-password', 'error' => $errors['password'] ?? null, 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'password_confirm', 'type' => 'password', 'label' => $view->t('admin.invitation.password_confirm'), 'autocomplete' => 'new-password', 'error' => $errors['password_confirm'] ?? null, 'required' => true]) ?>

      <?= $view->component('button', ['label' => $view->t('admin.invitation.submit'), 'variant' => 'admin-primary', 'type' => 'submit', 'class' => 'auth__submit']) ?>

    </form>
<?php endif; ?>
  </div>
</main>
</body>
</html>
