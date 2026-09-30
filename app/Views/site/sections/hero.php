<?php
/**
 * Home: hero with the field photo, the tagline, the headline (split into words by site.js for the rising motion)
 * and two buttons.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, title, highlight, intro, media, primary, secondary
 */
$media = $s['media'];
?>
<section class="hero">
  <div class="hero__bg" aria-hidden="true">
<?php if (is_array($media)): ?>
    <img src="<?= e_url($media['url']) ?>" alt="" width="<?= (int) $media['width'] ?>" height="<?= (int) $media['height'] ?>" fetchpriority="high">
<?php else: ?>
    <div class="ph ph--hero"><?= svg_icon('cedar', 'ph__icon') ?></div>
<?php endif; ?>
  </div>
  <div class="wrap">
    <div class="hero__content">
<?php if ($s['label'] !== ''): ?>
      <p class="eyebrow eyebrow--light hero__fade"><?= e($s['label']) ?></p>
<?php endif; ?>
      <h1 class="hero__title" data-split><?= e($s['title']) ?><?php if ($s['highlight'] !== ''): ?> <em><?= e($s['highlight']) ?></em><?php endif; ?></h1>
<?php if ($s['intro'] !== ''): ?>
      <p class="hero__text hero__fade hero__fade--2"><?= e($s['intro']) ?></p>
<?php endif; ?>
      <div class="hero__actions hero__fade hero__fade--3">
<?php if (is_array($s['primary'])): ?>
        <a class="btn btn--primary" href="<?= e_url($s['primary']['href']) ?>"><?= e($s['primary']['label']) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a>
<?php endif; ?>
<?php if (is_array($s['secondary'])): ?>
        <a class="btn btn--ghost" href="<?= e_url($s['secondary']['href']) ?>"><?= e($s['secondary']['label']) ?></a>
<?php endif; ?>
      </div>
    </div>
  </div>
<?php if (is_array($media) && $media['alt'] !== ''): ?>
  <p class="hero__caption"><?= e($media['alt']) ?></p>
<?php endif; ?>
</section>
