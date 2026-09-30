<?php
/**
 * @var \Gate\Core\View $view
 * @var list<array{key: string, ok: bool, required: bool, detail: string}> $checks
 * @var bool $passes
 */
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t('install.requirements.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.requirements.intro')) ?></span>
</div>
<div class="install-rows">
<?php foreach ($checks as $check): ?>
<?php
$pill = $check['ok']
    ? $view->component('status-pill', ['label' => $view->t('install.requirements.ok'), 'tone' => 'confirmed', 'dot' => true])
    : ($check['required']
        ? $view->component('status-pill', ['label' => $view->t('install.requirements.missing'), 'tone' => 'danger'])
        : $view->component('status-pill', ['label' => $view->t('install.requirements.warning'), 'tone' => 'diagnosis']));
?>
  <?= $view->component('setting-row', [
      'name' => $view->t('install.requirements.' . $check['key'], ['detail' => $check['detail']]),
      'description' => $check['detail'] . ($check['required'] ? '' : ' · ' . $view->t('install.requirements.recommended')),
      'control' => $pill,
  ]) ?>

<?php endforeach; ?>
</div>
<form class="actions" method="post" action="/install/requirements">
  <?= $view->csrfField() ?>
<?php if (!$passes): ?>
  <span class="muted install-note"><?= e($view->t('install.requirements.fix_first')) ?></span>
  <?= $view->component('button', ['label' => $view->t('install.requirements.recheck'), 'variant' => 'admin-secondary', 'href' => '/install/requirements', 'icon' => 'fa-solid fa-rotate-right', 'iconPosition' => 'start']) ?>

<?php endif; ?>
  <?= $view->component('button', ['label' => $view->t('install.next'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right', 'disabled' => !$passes]) ?>

</form>
