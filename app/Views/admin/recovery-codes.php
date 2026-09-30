<?php
/**
 * Recovery codes, shown once.
 *
 * @var \BMMatic\Core\View $view
 * @var list<string> $codes
 * @var string $adminPath
 */
?>
<div class="flex gap-20 agrow">
  <div class="acard agrow panel-fields">
    <div class="card-intro card-intro--flush">
      <h2 class="h3"><?= e($view->t('admin.two_factor.codes_heading')) ?></h2>
      <span class="muted"><?= e($view->t('admin.two_factor.codes_desc')) ?></span>
    </div>
    <?= $view->component('notice', ['title' => $view->t('admin.two_factor.codes_once_title'), 'text' => $view->t('admin.two_factor.codes_once_text')]) ?>

    <ul class="codes">
<?php foreach ($codes as $code): ?>
      <li><?= $view->component('status-pill', ['label' => $code, 'class' => 'code-value']) ?></li>
<?php endforeach; ?>
    </ul>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.two_factor.codes_saved'), 'variant' => 'admin-primary', 'href' => $adminPath . '/security', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
  </div>
</div>
