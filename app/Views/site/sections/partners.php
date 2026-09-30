<?php
/**
 * Home: partner and donor logos in a slow marquee (pauses on hover and focus; still with reduced motion).
 * The list is printed twice for a seamless loop; the copy is hidden from assistive technology.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: title, partners, href
 */
$item = static function (array $p, bool $copy) use ($view): string {
    $inner = $p['logo'] !== null
        ? '<img src="' . e_url($p['logo']['url']) . '" alt="' . e_attr($p['name']) . '" loading="lazy" decoding="async">'
        : '<span>' . e($p['name']) . '</span>';
    $tag = $p['url'] !== '' ? '<a href="' . e_url($p['url']) . '" rel="noopener" target="_blank"' . ($copy ? ' tabindex="-1"' : '') . '>' . $inner . '</a>' : '<div>' . $inner . '</div>';
    return '<li class="partner"' . ($copy ? ' aria-hidden="true"' : '') . '>' . $tag . '</li>';
};
?>
<section class="partners-strip" aria-labelledby="partners-title">
  <div class="wrap">
    <h2 class="partners-strip__title" id="partners-title"><?= e($s['title'] !== '' ? $s['title'] : $view->t('site.partners.title')) ?></h2>
    <div class="marquee" data-marquee>
      <ul class="marquee__track">
<?php foreach ($s['partners'] as $p): ?>
        <?= $item($p, false) ?>
<?php endforeach; ?>
<?php foreach ($s['partners'] as $p): ?>
        <?= $item($p, true) ?>
<?php endforeach; ?>
      </ul>
    </div>
<?php if (is_string($s['href'])): ?>
    <p class="partners-strip__more"><a class="link-more" href="<?= e_url($s['href']) ?>"><?= e($view->t('site.partners.all')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a></p>
<?php endif; ?>
  </div>
</section>
