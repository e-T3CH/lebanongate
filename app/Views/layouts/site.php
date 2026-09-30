<?php
/**
 * Public site layout: skip link, SVG sprite, top bar, header, main content, footer, back-to-top button, cookie
 * banner and flash toast. Data comes from SitePresenter::layout(); head data from SitePresenter::head().
 *
 * @var \Gate\Core\View $view
 * @var string $content
 * @var array<string, mixed> $head
 * @var array{lang: string, topbar: array<string, mixed>, header: array<string, mixed>, footer: array<string, mixed>, cookie: array<string, mixed>|null, toast: array{type: string, message: string}|null} $site
 */
?>
<?= $view->partial('head', ['bundle' => 'site'] + $head) ?>
<body class="site">
<a class="skip" href="#main"><?= e($view->t('site.skip')) ?></a>
<?= $view->render('site/parts/sprite') ?>
<?= $view->render('site/parts/topbar', ['topbar' => $site['topbar']]) ?>
<?= $view->render('site/parts/header', ['header' => $site['header']]) ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= $view->render('site/parts/footer', ['footer' => $site['footer']]) ?>
<button class="to-top" type="button" data-to-top aria-label="<?= e_attr($view->t('site.to_top')) ?>" hidden>
  <svg class="to-top__ring" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22"/><circle class="to-top__progress" cx="24" cy="24" r="22" pathLength="100"/></svg>
  <?= svg_icon('arrow-up', 'ic to-top__icon') ?>
</button>
<?php if ($site['cookie'] !== null): ?>
<?= $view->render('site/parts/cookie', ['cookie' => $site['cookie']]) ?>
<?php endif; ?>
<?php if ($site['toast'] !== null): ?>
<div class="toast toast--<?= e_attr($site['toast']['type']) ?>" role="status" data-toast>
  <?= svg_icon($site['toast']['type'] === 'error' ? 'alert' : ($site['toast']['type'] === 'success' ? 'check' : 'info'), 'ic toast__icon') ?>
  <p><?= e($site['toast']['message']) ?></p>
  <button type="button" class="toast__close" data-toast-close aria-label="<?= e_attr($view->t('site.close')) ?>"><?= svg_icon('close') ?></button>
</div>
<?php endif; ?>
</body>
</html>
