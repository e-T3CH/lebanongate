<?php
/**
 * Public site header: optional top bar (address, hours, phone; an <aside> so it sits in a landmark), logo, main navigation with a mega menu for items
 * with children, language selector, CTA button and the burger that opens the mobile menu (< 768px).
 * Parameters: Components::SPECS['site-header'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{nav: list<array{label: string, href: string, children?: list<array{title: string, sub?: string, href: string}>, current?: bool}>, languages: list<array<string, mixed>>, homeHref: string, logoSrc: string, logoAlt: string, siteName: string, ctaLabel: ?string, ctaHref: ?string, menuHref: string, topbar: ?array<string, mixed>, harness: bool} $p
 */

$icon = static fn (string $name, string $size = '16', string $class = ''): string => (string) $view->component('icon', ['icon' => $name, 'size' => $size, 'class' => $class]);
$str = static fn (array $a, string $k): string => is_string($a[$k] ?? null) ? $a[$k] : '';
?>
<?php if ($p['topbar'] !== null): ?>
<aside class="topbar" aria-label="<?= e_attr($view->t('ui.header.topbar')) ?>">
  <div class="topbar__info">
    <span class="topbar__item"><?= $icon('fa-solid fa-location-dot', '14', 'ic-accent') ?> <?= e($str($p['topbar'], 'address')) ?></span>
    <span class="topbar__item"><?= $icon('fa-regular fa-clock', '14', 'ic-accent') ?> <?= e($str($p['topbar'], 'hours')) ?></span>
<?php if ($str($p['topbar'], 'coords') !== ''): ?>
    <span class="topbar__coords"><?= e($str($p['topbar'], 'coords')) ?></span>
<?php endif; ?>
  </div>
  <a class="topbar__phone" href="<?= e_url($str($p['topbar'], 'phoneHref')) ?>"><?= $icon('fa-solid fa-phone', '14', 'ic-accent') ?> <?= e($str($p['topbar'], 'phone')) ?></a>
</aside>

<?php endif; ?>
<header class="hdr">
  <a class="hdr__logo" href="<?= e_url($p['homeHref']) ?>" aria-label="<?= e_attr($view->t('ui.header.home', ['site' => $p['siteName']])) ?>"><?= $view->component('picture', ['src' => $p['logoSrc'], 'alt' => $p['logoAlt'], 'width' => 1000, 'height' => 398, 'widths' => [141, 282], 'sizes' => '(max-width: 1023px) 96px, 141px', 'loading' => 'eager', 'priority' => true]) ?></a>
  <nav class="nav hdr__nav" aria-label="<?= e_attr($view->t('ui.header.main_nav')) ?>">
<?php foreach ($p['nav'] as $item): ?>
<?php if (!empty($item['children']) && is_array($item['children'])): ?>
    <div class="navitem" data-menu<?= $p['harness'] ? ' data-state="services"' : '' ?>>
      <a href="<?= e_url($item['href']) ?>" aria-haspopup="true" aria-expanded="false" data-menu-trigger><?= e($item['label']) ?> <?= $icon('fa-solid fa-chevron-down', '14', 'ic-chev') ?></a>
      <div class="mega m-pop" data-menu-panel>
<?php foreach ($item['children'] as $child): ?>
        <a href="<?= e_url($str($child, 'href')) ?>"><span class="mega__title"><?= e($str($child, 'title')) ?></span><?php if ($str($child, 'sub') !== ''): ?><span class="mega__sub"><?= e($str($child, 'sub')) ?></span><?php endif; ?></a>
<?php endforeach; ?>
      </div>
    </div>
<?php else: ?>
    <a href="<?= e_url($item['href']) ?>"<?= ($item['current'] ?? false) === true ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
<?php endif; ?>
<?php endforeach; ?>
  </nav>
  <div class="hdr__actions">
    <?= $view->component('language-selector', ['languages' => $p['languages'], 'variant' => 'desktop', 'class' => 'hdr__lang', 'attrs' => $p['harness'] ? ['data-state' => 'language'] : []]) ?>

<?php if ($p['ctaLabel'] !== null && $p['ctaHref'] !== null): ?>
    <?= $view->component('button', ['label' => $p['ctaLabel'], 'href' => $p['ctaHref'], 'icon' => 'fa-solid fa-arrow-right', 'class' => 'hdr__book']) ?>

<?php endif; ?>
    <a class="iconbtn hdr__burger" href="<?= e_url($p['menuHref']) ?>" aria-label="<?= e_attr($view->t('ui.header.open_menu')) ?>" aria-controls="mnav" aria-expanded="false" data-drawer-open="mnav"><?= $icon('fa-solid fa-bars', '20') ?></a>
  </div>
</header>
