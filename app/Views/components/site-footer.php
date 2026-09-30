<?php
/**
 * Public site footer: logo, short description, social links, link columns, copyright/VAT line and the
 * language links (also the no-JavaScript way to switch language).
 * Parameters: Components::SPECS['site-footer'].
 *
 * @var \Gate\Core\View $view
 * @var array{logoSrc: string, logoAlt: string, about: string, columns: list<array{title: string, links: list<array{label: string, href: string}>}>, copyright: string, languages: list<array{name: string, href?: ?string, code?: string}>, socials: list<array{network: string, url: string}>} $p
 */

$str = static fn (array $a, string $k): string => is_string($a[$k] ?? null) ? $a[$k] : '';
$langs = [];
$linked = false;
foreach ($p['languages'] as $l) {
    if (!is_array($l)) {
        continue;
    }
    if ($str($l, 'href') !== '') {
        $linked = true;
        $langs[] = '<a href="' . e_url($str($l, 'href')) . '"' . ($str($l, 'code') !== '' ? ' hreflang="' . e_attr($str($l, 'code')) . '" lang="' . e_attr($str($l, 'code')) . '"' : '') . '>' . e($str($l, 'name')) . '</a>';
    } else {
        $langs[] = e($str($l, 'name'));
    }
}
$langList = implode(' · ', $langs);
?>
<footer class="ftr">
  <div class="ftr__top">
    <div class="ftr__brand">
      <?= $view->component('picture', ['src' => $p['logoSrc'], 'alt' => $p['logoAlt'], 'width' => 1000, 'height' => 398, 'widths' => [121, 161, 242, 322], 'sizes' => '(max-width: 767px) 121px, 161px', 'class' => 'ftr__logo']) ?>
      <p class="ftr__about"><?= e($p['about']) ?></p>
      <?= $view->component('social-links', ['links' => $p['socials']]) ?>

    </div>
    <div class="ftr__cols">
<?php foreach ($p['columns'] as $col): ?>
      <div class="ftr__col"><span class="cap cap--14"><?= e($str($col, 'title')) ?></span><?php foreach (is_array($col['links'] ?? null) ? $col['links'] : [] as $link): ?><a href="<?= e_url($str($link, 'href')) ?>"><?= e($str($link, 'label')) ?></a><?php endforeach; ?></div>
<?php endforeach; ?>
    </div>
  </div>
  <div class="ftr__bottom">
    <span><?= e($p['copyright']) ?></span>
<?php if ($langs !== []): ?>
    <span class="ftr__langs"><?= $view->component('icon', ['icon' => 'fa-solid fa-globe', 'size' => '14']) ?> <?= $linked ? '<span class="ftr__langs-list">' . $langList . '</span>' : $langList ?></span>
<?php endif; ?>
  </div>
</footer>
