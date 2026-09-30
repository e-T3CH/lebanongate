<?php
/**
 * @var \Gate\Core\View $view
 * @var array{name: string, email: string} $admin
 * @var array<string, string> $errors
 */
$err = static fn (string $f): ?string => isset($errors[$f]) ? $view->t($errors[$f], ['min' => \Gate\Security\PasswordHasher::MIN_LENGTH]) : null;
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t('install.admin.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.admin.intro')) ?></span>
</div>
<form class="panel-fields panel-fields--flush" method="post" action="/install/admin" novalidate>
  <?= $view->csrfField() ?>
  <div class="fields-2">
    <?= $view->component('input', ['name' => 'name', 'label' => $view->t('install.admin.name'), 'value' => $admin['name'], 'autocomplete' => 'name', 'required' => true, 'error' => $err('name')]) ?>
    <?= $view->component('input', ['name' => 'email', 'type' => 'email', 'label' => $view->t('install.admin.email'), 'value' => $admin['email'], 'autocomplete' => 'username', 'required' => true, 'error' => $err('email')]) ?>
    <?= $view->component('input', ['name' => 'password', 'type' => 'password', 'label' => $view->t('install.admin.password'), 'autocomplete' => 'new-password', 'required' => true, 'hint' => $view->t('install.admin.password_hint', ['min' => \Gate\Security\PasswordHasher::MIN_LENGTH]), 'error' => $err('password')]) ?>
    <?= $view->component('input', ['name' => 'password_confirm', 'type' => 'password', 'label' => $view->t('install.admin.password_confirm'), 'autocomplete' => 'new-password', 'required' => true, 'error' => $err('password_confirm')]) ?>
  </div>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('install.back'), 'variant' => 'admin-secondary', 'href' => '/install/site']) ?><?= $view->component('button', ['label' => $view->t('install.next'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right']) ?></div>
</form>
