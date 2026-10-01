<?php
/**
 * @var \Gate\Core\View $view
 * @var array{site_name: string, site_url: string} $site
 * @var array<string, string> $errors
 */
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t('install.site.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.site.intro')) ?></span>
</div>
<form class="panel-fields panel-fields--flush" method="post" action="<?= e_url('/install/site') ?>" novalidate>
  <?= $view->csrfField() ?>
  <div class="fields-2">
    <?= $view->component('input', ['name' => 'site_name', 'label' => $view->t('install.site.name'), 'value' => $site['site_name'], 'required' => true, 'error' => isset($errors['site_name']) ? $view->t($errors['site_name']) : null]) ?>
    <?= $view->component('input', ['name' => 'site_url', 'type' => 'url', 'label' => $view->t('install.site.url'), 'value' => $site['site_url'], 'required' => true, 'hint' => $view->t('install.site.url_hint'), 'error' => isset($errors['site_url']) ? $view->t($errors['site_url']) : null]) ?>
  </div>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('install.back'), 'variant' => 'admin-secondary', 'href' => '/install/database']) ?><?= $view->component('button', ['label' => $view->t('install.next'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right']) ?></div>
</form>
