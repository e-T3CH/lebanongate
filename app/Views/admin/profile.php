<?php
/**
 * My profile: name and email address (a change is confirmed from the new address) and the password
 * (current password required; changing it signs out the other sessions).
 *
 * @var \BMMatic\Core\View $view
 * @var array{id: int, name: string, email: string, role: string} $profile
 * @var string|null $pendingEmail
 * @var array<string, string> $errors
 * @var array<string, string> $passwordErrors
 * @var array<string, mixed> $old
 * @var string $adminPath
 */
$value = static fn (string $key, string $fallback): string => is_scalar($old[$key] ?? null) ? (string) $old[$key] : $fallback;
?>
<div class="flex gap-20 agrow">
  <form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/profile') ?>" novalidate>
    <?= $view->csrfField() ?>
    <div class="sec-card__head">
      <h2 class="h3"><?= e($view->t('admin.profile.card_title')) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t('admin.profile.role_label', ['role' => $view->t('admin.roles.' . $profile['role'])])]) ?>

    </div>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'name', 'label' => $view->t('admin.profile.name'), 'value' => $value('name', $profile['name']), 'error' => $errors['name'] ?? null, 'autocomplete' => 'name', 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'email', 'type' => 'email', 'label' => $view->t('admin.profile.email'), 'value' => $value('email', $profile['email']), 'error' => $errors['email'] ?? null, 'hint' => $view->t('admin.profile.email_hint'), 'autocomplete' => 'email', 'required' => true]) ?>

    </div>
<?php if ($pendingEmail !== null): ?>
    <?= $view->component('notice', ['title' => $view->t('admin.profile.email_pending', ['email' => $pendingEmail])]) ?>

<?php endif; ?>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
  </form>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/profile/password') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.profile.password_title')) ?></h2><span class="muted"><?= e($view->t('admin.profile.password_desc')) ?></span></div>
      <?= $view->component('input', ['name' => 'current_password', 'type' => 'password', 'label' => $view->t('admin.profile.current_password'), 'autocomplete' => 'current-password', 'error' => $passwordErrors['current_password'] ?? null, 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('admin.profile.new_password'), 'autocomplete' => 'new-password', 'error' => $passwordErrors['password'] ?? null, 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'password_confirm', 'type' => 'password', 'label' => $view->t('admin.profile.new_password_confirm'), 'autocomplete' => 'new-password', 'error' => $passwordErrors['password_confirm'] ?? null, 'required' => true]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.profile.change_password'), 'variant' => 'admin-secondary', 'type' => 'submit']) ?></div>
    </form>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.profile.two_factor_title')) ?></h2><span class="muted"><?= e($view->t('admin.security.two_factor_off_desc')) ?></span></div>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.profile.two_factor_link'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/security', 'icon' => 'fa-solid fa-shield-halved', 'iconPosition' => 'start']) ?></div>
    </div>
  </div>
</div>
