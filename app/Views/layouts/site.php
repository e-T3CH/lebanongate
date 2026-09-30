<?php
/**
 * Public site layout: skip link, header (with the optional top bar), mobile menu, main content, footer, mobile dock,
 * cookie banner and flash toast. All parts are components; the data comes from SitePresenter (or the design-check
 * fixtures for the approved screens).
 *
 * @var \Gate\Core\View $view
 * @var string $content
 * @var array<string, mixed> $head partial('head') parameters
 * @var array{header: array<string, mixed>, drawer: array<string, mixed>, footer: array<string, mixed>, dock: array<string, mixed>|null, cookie: array<string, mixed>|null, toast: array{type: string, message: string}|null} $site
 */
?>
<?php
$bundles = is_array($head['bundles'] ?? null) ? $head['bundles'] : [];
if ($site['cookie'] !== null) {
    $bundles[] = 'cookie';
}
if ($site['toast'] !== null) {
    $bundles[] = 'toast';
}
?>
<?= $view->partial('head', ['bundle' => 'site', 'bundles' => $bundles] + $head) ?>
<body class="site grid-bg dk">
<a class="skip" href="#main"><?= e($view->t('site.skip')) ?></a>
<div class="page">
<?= $view->component('site-header', $site['header']) ?>

<?= $view->component('mobile-menu', $site['drawer']) ?>


<main class="page__main" id="main">
<?= $content ?>
</main>

<?= $view->component('site-footer', $site['footer']) ?>

<?php if ($site['dock'] !== null): ?>

<?= $view->component('mobile-dock', $site['dock']) ?>

<?php endif; ?>
</div>
<?php if ($site['cookie'] !== null): ?>
<?= $view->component('cookie-banner', $site['cookie']) ?>

<?php endif; ?>
<?php if ($site['toast'] !== null): ?>
<?= $view->component('toast', ['type' => $site['toast']['type'], 'message' => $site['toast']['message']]) ?>

<?php endif; ?>
</body>
</html>
