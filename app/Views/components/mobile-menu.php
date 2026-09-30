<?php
/**
 * Mobile menu drawer (full screen, < 768px): logo, close button, numbered menu rows with sub-items,
 * language segmented control, CTA and social links. Opened by the header burger (ui.js); without JavaScript the
 * burger links to a page that renders it open.
 * Parameters: Components::SPECS['mobile-menu'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{nav: list<array{label: string, href: string, children?: list<array{title: string, href: string}>}>, languages: list<array<string, mixed>>, logoSrc: string, logoAlt: string, closeHref: string, ctaLabel: ?string, ctaHref: ?string, socials: list<array{network: string, url: string}>, open: bool, hidden: bool, id: string} $p
 */

use BMMatic\Core\Props;

$str = static fn (array $a, string $k): string => is_string($a[$k] ?? null) ? $a[$k] : '';
?>
<div class="<?= e_attr(Props::classes('mnav m-drawer', $p['open'] ? 'is-open' : null)) ?>" id="<?= e_attr($p['id']) ?>" role="dialog" aria-modal="true" aria-label="<?= e_attr($view->t('ui.menu.label')) ?>"<?= $p['hidden'] ? ' hidden' : '' ?>>
  <div class="mnav__head">
    <?= $view->component('picture', ['src' => $p['logoSrc'], 'alt' => $p['logoAlt'], 'width' => 1000, 'height' => 398, 'widths' => [96, 192], 'sizes' => '96px']) ?>
    <a class="iconbtn" href="<?= e_url($p['closeHref']) ?>" aria-label="<?= e_attr($view->t('ui.menu.close')) ?>" data-drawer-close="<?= e_attr($p['id']) ?>"><?= $view->component('icon', ['icon' => 'fa-solid fa-xmark', 'size' => '20']) ?></a>
  </div>
  <nav class="mnav__nav m-menu" aria-label="<?= e_attr($view->t('ui.header.main_nav')) ?>">
<?php foreach ($p['nav'] as $i => $item): ?>
<?php $num = sprintf('%02d', $i + 1); ?>
<?php if (!empty($item['children']) && is_array($item['children'])): ?>
    <a class="mnav__row mnav__row--parent" href="<?= e_url($item['href']) ?>"><span class="mnav__row-label"><span class="mnav__num"><?= $num ?></span><?= e($item['label']) ?></span><?= $view->component('icon', ['icon' => 'fa-solid fa-chevron-down', 'size' => '18', 'class' => 'ic-accent ic-chev']) ?></a>
    <div class="mnav__sub">
<?php foreach ($item['children'] as $child): ?>
      <a href="<?= e_url($str($child, 'href')) ?>"><?= e($str($child, 'title')) ?></a>
<?php endforeach; ?>
    </div>
<?php else: ?>
    <a class="mnav__row" href="<?= e_url($item['href']) ?>"><span class="mnav__num"><?= $num ?></span><?= e($item['label']) ?></a>
<?php endif; ?>
<?php endforeach; ?>
  </nav>
  <?= $view->component('language-selector', ['languages' => $p['languages'], 'variant' => 'mobile']) ?>

  <div class="mnav__foot">
<?php if ($p['ctaLabel'] !== null && $p['ctaHref'] !== null): ?>
    <?= $view->component('button', ['label' => $p['ctaLabel'], 'href' => $p['ctaHref'], 'icon' => 'fa-solid fa-arrow-right']) ?>

<?php endif; ?>
    <div class="mnav__foot-row"><?= $view->component('social-links', ['links' => $p['socials']]) ?></div>
  </div>
</div>
