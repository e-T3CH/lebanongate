<?php
/**
 * @var \BMMatic\Core\View $view
 * @var string $loginUrl
 * @var string $email
 */
?>
<div class="card-intro card-intro--flush">
  <?= $view->component('status-pill', ['label' => $view->t('install.done.badge'), 'tone' => 'confirmed', 'dot' => true]) ?>

  <h1 class="atop__title"><?= e($view->t('install.done.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.done.intro')) ?></span>
</div>
<?= $view->component('setting-row', ['name' => $view->t('install.done.login_url'), 'description' => $view->t('install.done.login_url_hint'), 'control' => '']) ?>

<p class="secret-key"><?= $view->component('status-pill', ['label' => $loginUrl, 'class' => 'code-value']) ?></p>
<?= $view->component('notice', ['title' => $view->t('install.done.two_factor_title'), 'text' => $view->t('install.done.two_factor_text')]) ?>

<p class="muted"><?= e($view->t('install.done.account', ['email' => $email])) ?></p>
<div class="actions"><?= $view->component('button', ['label' => $view->t('install.done.go'), 'variant' => 'admin-primary', 'href' => $loginUrl, 'icon' => 'fa-solid fa-arrow-right']) ?></div>
