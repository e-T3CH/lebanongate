<?php
/**
 * Sign-in screens: admin workspace background with a centered card (admin components only).
 *
 * @var \Gate\Core\View $view
 * @var string $content
 */
?>
<?= $view->partial('head', ['title' => $view->t('admin.login.document_title'), 'bundle' => 'admin', 'noindex' => true]) ?>
<body class="admin">
<main class="auth">
  <div class="acard auth__card">
    <div class="sb__brand auth__brand"><span class="sb__mark"><img src="<?= e_attr($view->asset('img/gate-mark.png')) ?>" alt="" width="40" height="40"></span><span class="sb__name"><span class="auth__title">GATE LEBANON</span><span class="sb__sub"><?= e($view->t('admin.nav.panel')) ?></span></span></div>
<?= $content ?>
  </div>
  <?= $view->partial('credit') ?>
</main>
</body>
</html>
