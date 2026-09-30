<?php
/**
 * Generic error page (no details are ever shown to visitors).
 *
 * @var \BMMatic\Core\View $view
 * @var int $status
 * @var string $key
 * @var string|null $reference error log reference (500/503)
 */
?>
<?= $view->partial('head', ['title' => $status . ' — ' . $view->t('site.errors.' . $key . '_title'), 'bundle' => 'admin', 'noindex' => true]) ?>
<body class="admin">
<main class="auth">
  <div class="acard auth__card">
    <div class="auth__intro">
      <?= $view->component('status-pill', ['label' => (string) $status]) ?>

      <h1 class="atop__title"><?= e($view->t('site.errors.' . $key . '_title')) ?></h1>
      <p class="muted"><?= e($view->t('site.errors.' . $key . '_text')) ?></p>
<?php if (isset($reference) && is_string($reference)): ?>
      <p class="muted"><?= e($view->t('site.errors.reference', ['code' => $reference])) ?></p>
<?php endif; ?>
    </div>
    <?= $view->component('button', ['label' => $view->t('site.errors.home'), 'variant' => 'admin-secondary', 'href' => '/', 'class' => 'auth__submit']) ?>

  </div>
</main>
</body>
</html>
