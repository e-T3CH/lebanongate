<?php
/**
 * Sticky header: logo, main navigation (About opens its child pages), call to action, mobile menu toggle.
 *
 * @var \Gate\Core\View $view
 * @var array{homeHref: string, logo: string|null, siteName: string, nav: list<array{key: string, label: string, href: string, current: bool, children: list<array{label: string, href: string, current: bool}>}>, cta: array{label: string, href: string}} $header
 */
?>
<header class="header" data-header>
  <div class="wrap header__row">
    <?= $view->render('site/parts/logo', ['logo' => $header['logo'], 'siteName' => $header['siteName'], 'href' => $header['homeHref'], 'variant' => 'header']) ?>
    <nav class="nav" id="site-nav" aria-label="<?= e_attr($view->t('site.nav.main')) ?>" data-nav>
      <ul class="nav__list">
<?php foreach ($header['nav'] as $i => $item): ?>
<?php if ($item['children'] !== []): ?>
        <li class="nav__item nav__item--dd" data-dropdown>
          <a class="nav__link" href="<?= e_url($item['href']) ?>"<?= $item['current'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
          <button type="button" class="nav__toggle" aria-expanded="false" aria-controls="sub-<?= (int) $i ?>" aria-label="<?= e_attr($view->t('site.nav.submenu', ['name' => $item['label']])) ?>"><?= svg_icon('caret', 'ic ic--caret') ?></button>
          <ul class="nav__sub" id="sub-<?= (int) $i ?>">
            <li><a href="<?= e_url($item['href']) ?>"><?= e($item['label']) ?></a></li>
<?php foreach ($item['children'] as $child): ?>
            <li><a href="<?= e_url($child['href']) ?>"<?= $child['current'] ? ' aria-current="page"' : '' ?>><?= e($child['label']) ?></a></li>
<?php endforeach; ?>
          </ul>
        </li>
<?php else: ?>
        <li class="nav__item"><a class="nav__link" href="<?= e_url($item['href']) ?>"<?= $item['current'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
      </ul>
      <a class="btn btn--primary nav__cta" href="<?= e_url($header['cta']['href']) ?>"><?= e($header['cta']['label']) ?></a>
    </nav>
    <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="site-nav" data-menu-toggle>
      <?= svg_icon('menu', 'ic menu-toggle__open') ?><?= svg_icon('close', 'ic menu-toggle__close') ?>
      <span class="sr-only"><?= e($view->t('site.nav.menu')) ?></span>
    </button>
  </div>
</header>
