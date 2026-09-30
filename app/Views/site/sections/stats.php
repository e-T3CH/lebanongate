<?php
/**
 * Home: impact counters on the deep blue band. Numbers count up once when they come into view (site.js); the real
 * value is in the HTML, so without JavaScript or with reduced motion the final numbers show.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, stats
 */
?>
<section class="band" aria-label="<?= e_attr($s['label'] !== '' ? $s['label'] : $view->t('site.stats.label')) ?>">
  <?= svg_icon('cedar', 'band__cedar') ?>
  <div class="wrap stats" data-stagger>
<?php foreach ($s['stats'] as $stat): ?>
<?php $plus = str_ends_with(trim($stat['value']), '+'); $value = rtrim(trim($stat['value']), '+'); ?>
    <div class="stat reveal">
      <p class="stat__num"><span<?= preg_match('/^[0-9][0-9,.\s]*$/', $value) === 1 ? ' data-count="' . e_attr((string) preg_replace('/\D/', '', $value)) . '"' : '' ?>><?= e($value) ?></span><?php if ($plus): ?><sup>+</sup><?php endif; ?></p>
      <p class="stat__label"><?= e($stat['label']) ?></p>
    </div>
<?php endforeach; ?>
  </div>
</section>
