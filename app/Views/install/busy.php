<?php
/**
 * @var \BMMatic\Core\View $view
 * @var bool|null $notFound
 */
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t(!empty($notFound) ? 'install.busy.not_found_title' : 'install.busy.title')) ?></h1>
  <span class="muted"><?= e($view->t(!empty($notFound) ? 'install.busy.not_found_text' : 'install.busy.text')) ?></span>
</div>
<div class="actions"><?= $view->component('button', ['label' => $view->t('install.busy.retry'), 'variant' => 'admin-secondary', 'href' => '/install/requirements']) ?></div>
