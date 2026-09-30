<?php
/**
 * @var \BMMatic\Core\View $view
 * @var array{host: string, port: int, name: string, user: string, pass: string} $db
 * @var string|null $error
 */
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t('install.database.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.database.intro')) ?></span>
</div>
<?php if ($error !== null): ?>
<?= $view->component('notice', ['title' => $view->t($error), 'icon' => 'fa-solid fa-database', 'role' => 'alert']) ?>

<?php endif; ?>
<form class="panel-fields panel-fields--flush" method="post" action="/install/database" novalidate>
  <?= $view->csrfField() ?>
  <div class="fields-2">
    <?= $view->component('input', ['name' => 'db_host', 'label' => $view->t('install.database.host'), 'value' => $db['host'], 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'db_port', 'label' => $view->t('install.database.port'), 'value' => (string) $db['port'], 'inputmode' => 'numeric']) ?>
    <?= $view->component('input', ['name' => 'db_name', 'label' => $view->t('install.database.name'), 'value' => $db['name'], 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'db_user', 'label' => $view->t('install.database.user'), 'value' => $db['user'], 'autocomplete' => 'off', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'db_pass', 'type' => 'password', 'label' => $view->t('install.database.pass'), 'autocomplete' => 'new-password']) ?>
  </div>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('install.back'), 'variant' => 'admin-secondary', 'href' => '/install/requirements']) ?><?= $view->component('button', ['label' => $view->t('install.database.test_and_continue'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right']) ?></div>
</form>
