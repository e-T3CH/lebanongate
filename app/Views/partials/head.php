<?php
/**
 * Document head.
 * - <html data-motion="standard|subtle|off">: the Appearance motion setting (motion.js and motion.css read it).
 * - The inline head script (CSP nonce) adds .js and, unless motion is off or reduced, .motion before CSS paints; it also
 *   quietens the promises of a page transition the browser skips (registered before the first paint).
 * - Theme tokens changed in the Appearance settings, in a nonce'd <style>: no inline style attributes anywhere.
 * - CSS: "admin" (admin panel, sign-in, installer), "design-check", or the public core plus per-page groups
 *   ($bundles: home, cards, stats, reviews, forms, content; cookie and toast only when shown; motion-off for the "off" setting).
 * - SEO: description, canonical, hreflang, Open Graph, JSON-LD; analytics only after consent.
 *
 * @var \Gate\Core\View $view
 * @var string $title
 * @var string|null $bundle
 * @var list<string>|null $bundles
 * @var bool|null $noindex
 * @var string|null $canonical
 * @var list<array{hreflang: string, href: string}>|null $alternates
 * @var string|null $description
 * @var array{title: string, description: string, url: string, image: string, locale: string, site_name: string, type: string, alternate_locales: list<string>}|null $og
 * @var list<array<string, mixed>>|null $jsonld
 * @var array{scripts: list<array{src: string, attrs: array<string, string>}>, inline: string}|null $analytics
 * @var list<array{href: string, srcset: string, sizes: string}>|null $preloadImages
 * @var array{icon: string, touch: string}|null $favicon the Appearance favicon (defaults to the BM monogram)
 * @var bool|null $harness loads the ?state= visual-check harness (design check only)
 */
$motion = $view->shared('motion', 'standard');
$motion = is_string($motion) && in_array($motion, \Gate\Core\ThemeConfig::MOTION_MODES, true) ? $motion : 'standard';
$themeCss = $view->shared('themeCss', '');
$bundle = in_array($bundle ?? 'site', ['site', 'admin', 'design-check'], true) ? ($bundle ?? 'site') : 'site';
$styles = $bundle === 'site'
    ? array_merge(['core'], array_values(array_intersect(['home', 'cards', 'stats', 'reviews', 'forms', 'content', 'cookie', 'toast'], $bundles ?? [])), $motion === 'off' ? ['motion-off'] : [])
    : [$bundle];
$nonce = e_attr($view->nonce());
?><!doctype html>
<html lang="<?= e_attr($view->locale()) ?>" data-motion="<?= e_attr($motion) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
<?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<?php if (!empty($description)): ?>
  <meta name="description" content="<?= e_attr($description) ?>">
<?php endif; ?>
<?php if (!empty($canonical)): ?>
  <link rel="canonical" href="<?= e_url($canonical) ?>">
<?php endif; ?>
<?php foreach ($alternates ?? [] as $alt): ?>
  <link rel="alternate" hreflang="<?= e_attr($alt['hreflang']) ?>" href="<?= e_url($alt['href']) ?>">
<?php endforeach; ?>
<?php if (!empty($og)): ?>
  <meta property="og:type" content="<?= e_attr($og['type']) ?>">
  <meta property="og:site_name" content="<?= e_attr($og['site_name']) ?>">
  <meta property="og:title" content="<?= e_attr($og['title']) ?>">
  <meta property="og:description" content="<?= e_attr($og['description']) ?>">
  <meta property="og:url" content="<?= e_url($og['url']) ?>">
  <meta property="og:image" content="<?= e_url($og['image']) ?>">
  <meta property="og:locale" content="<?= e_attr($og['locale']) ?>">
<?php foreach ($og['alternate_locales'] as $locale): ?>
  <meta property="og:locale:alternate" content="<?= e_attr($locale) ?>">
<?php endforeach; ?>
  <meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<?php if (isset($favicon) && is_array($favicon)): ?>
  <link rel="icon" href="<?= e_url($favicon['icon']) ?>">
  <link rel="apple-touch-icon" href="<?= e_url($favicon['touch']) ?>">
<?php else: ?>
  <link rel="icon" href="<?= e_attr($view->asset('img/favicon-32.png')) ?>">
<?php endif; ?>
  <script nonce="<?= $nonce ?>">(function (d) { d.classList.add('js'); if (d.getAttribute('data-motion') !== 'off' && !matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) d.classList.add('motion'); addEventListener('pagereveal', function (e) { var t = e.viewTransition; if (t) [t.ready, t.finished].forEach(function (p) { p.catch(function () {}); }); }); })(document.documentElement);</script>
  <link rel="preload" href="<?= e_attr($view->asset('fonts/Montserrat-VF.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php if ($bundle !== 'admin'): ?>
  <link rel="preload" href="<?= e_attr($view->asset('fonts/Montserrat-Italic-VF.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php endif; ?>
<?php foreach ($preloadImages ?? [] as $image): ?>
  <link rel="preload" as="image" href="<?= e_url($image['href']) ?>" imagesrcset="<?= e_attr($image['srcset']) ?>" imagesizes="<?= e_attr($image['sizes']) ?>" fetchpriority="high">
<?php endforeach; ?>
<?php foreach ($styles as $style): ?>
  <link rel="stylesheet" href="<?= e_attr($view->asset('css/' . $style . '.css')) ?>">
<?php endforeach; ?>
<?php if (is_string($themeCss) && $themeCss !== ''): ?>
  <style nonce="<?= $nonce ?>"><?= $themeCss /* ThemeConfig::css(): validated token values only */ ?></style>
<?php endif; ?>
  <script type="application/json" id="bm-i18n"><?= e_js(['saved' => $view->t('ui.toast.saved'), 'save_failed' => $view->t('ui.toast.save_failed'), 'visible' => $view->t('ui.review.visible'), 'hidden' => $view->t('ui.review.hidden'), 'language.current' => $view->t('ui.language.current', ['name' => ':name'])]) ?></script>
  <script src="<?= e_attr($view->asset('js/app.js')) ?>" defer></script>
<?php if (!empty($harness)): ?>
  <script src="<?= e_attr($view->asset('js/state.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($bundle === 'design-check'): ?>
  <script src="<?= e_attr($view->asset('js/design-check.js')) ?>" defer></script>
<?php endif; ?>
<?php foreach ($jsonld ?? [] as $data): ?>
  <script type="application/ld+json"><?= \Gate\Site\Seo::jsonLd($data) ?></script>
<?php endforeach; ?>
<?php if (!empty($analytics)): ?>
<?php foreach ($analytics['scripts'] as $script): ?>
  <script src="<?= e_url($script['src']) ?>"<?= \Gate\Core\Props::attrs($script['attrs']) ?> async></script>
<?php endforeach; ?>
<?php if ($analytics['inline'] !== ''): ?>
  <script nonce="<?= $nonce ?>"><?= $analytics['inline'] /* built from validated IDs (Consent::analyticsTags) */ ?></script>
<?php endif; ?>
<?php endif; ?>
</head>
