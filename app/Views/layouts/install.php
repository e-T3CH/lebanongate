<?php
/**
 * Installer: centered wide card with the five steps as tabs and a language switch.
 *
 * @var \Gate\Core\View $view
 * @var string $content
 * @var list<string> $steps
 * @var string $step
 * @var array<string, bool> $done
 */
$current = $view->locale();
?>
<?= $view->partial('head', ['title' => $view->t('install.document_title'), 'bundle' => 'admin', 'noindex' => true]) ?>
<body class="admin">
<main class="auth">
  <div class="acard auth__card auth__card--wide">
    <div class="between">
      <div class="sb__brand auth__brand"><span class="sb__mark">BM</span><span class="sb__name"><span class="auth__title">BM-MATIC</span><span class="sb__sub"><?= e($view->t('install.caption')) ?></span></span></div>
      <nav class="auth__langs" aria-label="<?= e_attr($view->t('install.language_switch')) ?>">
<?php foreach (\Gate\I18n\LanguageRules::ADMIN as $code): ?>
        <a class="link-sm<?= $code === $current ? ' is-current' : '' ?>" href="?lang=<?= e_attr($code) ?>" lang="<?= e_attr($code) ?>"<?= $code === $current ? ' aria-current="true"' : '' ?>><?= e(strtoupper($code)) ?></a>
<?php endforeach; ?>
      </nav>
    </div>
<?php if ($step !== 'done'): ?>
    <ol class="tabs tabs--settings install-steps">
<?php foreach ($steps as $i => $s): ?>
      <li><span class="<?= $s === $step ? 'on' : '' ?><?= isset($done[$s]) && $s !== $step ? ' is-done' : '' ?>"<?= $s === $step ? ' aria-current="step"' : '' ?>><?= e(($i + 1) . '. ' . $view->t('install.steps.' . $s)) ?></span></li>
<?php endforeach; ?>
    </ol>
<?php endif; ?>
<?= $content ?>
  </div>
</main>
</body>
</html>
