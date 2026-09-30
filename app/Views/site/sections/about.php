<?php
/**
 * Home: about intro with a photo collage, the "since" badge, three value points and a link to About us.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, title, intro, media, media2, extra{points: list<{title, text, icon}>, badge_value, badge_label}, href
 */
$points = is_array($s['extra']['points'] ?? null) ? $s['extra']['points'] : [];
$badgeValue = is_string($s['extra']['badge_value'] ?? null) ? $s['extra']['badge_value'] : '';
$badgeLabel = is_string($s['extra']['badge_label'] ?? null) ? $s['extra']['badge_label'] : '';
$photo = static function (mixed $media, string $class): string {
    if (!is_array($media)) {
        return '<div class="' . $class . ' ph" aria-hidden="true">' . svg_icon('cedar', 'ph__icon') . '</div>';
    }
    return '<img class="' . $class . '" src="' . e_url($media['url']) . '" alt="' . e_attr($media['alt']) . '" width="' . (int) $media['width'] . '" height="' . (int) $media['height'] . '" loading="lazy" decoding="async">';
};
?>
<section class="section" id="about">
  <div class="wrap about-intro">
    <div class="collage reveal reveal--start">
      <?= $photo($s['media'], 'collage__a') ?>
      <?= $photo($s['media2'], 'collage__b') ?>
<?php if ($badgeValue !== ''): ?>
      <div class="collage__badge"><b><?= e($badgeValue) ?></b><span><?= e($badgeLabel) ?></span></div>
<?php endif; ?>
    </div>
    <div class="about-intro__text" data-stagger>
<?php if ($s['label'] !== ''): ?>
      <p class="eyebrow reveal"><?= e($s['label']) ?></p>
<?php endif; ?>
      <h2 class="h2 reveal"><?= e($s['title']) ?></h2>
<?php if ($s['intro'] !== ''): ?>
      <p class="lead reveal"><?= nl2br(e($s['intro']), false) ?></p>
<?php endif; ?>
<?php if ($points !== []): ?>
      <ul class="values">
<?php foreach ($points as $point): ?>
<?php if (!is_array($point)) { continue; } ?>
        <li class="reveal"><span class="values__icon"><?= svg_icon(is_string($point['icon'] ?? null) ? $point['icon'] : 'check') ?></span><div><h3><?= e((string) ($point['title'] ?? '')) ?></h3><p><?= e((string) ($point['text'] ?? '')) ?></p></div></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php if (is_string($s['href'])): ?>
      <a class="btn btn--outline reveal" href="<?= e_url($s['href']) ?>"><?= e($view->t('site.about.more')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a>
<?php endif; ?>
    </div>
  </div>
</section>
