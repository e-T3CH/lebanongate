<?php
/**
 * Home section "hero": label, H1 with highlight, lead, CTAs, Google rating and the blueprint (desktop art column;
 * the mobile schematic band with its legend follows below 768px).
 *
 * @var \BMMatic\Core\View $view
 * @var array{number: ?string, label: string, title: string, highlight: string, intro: string, extra: array<string, mixed>} $section
 * @var array<string, mixed> $home
 */
$extra = $section['extra'];
$str = static fn (string $k): string => is_string($extra[$k] ?? null) ? $extra[$k] : '';
$legend = array_values(array_filter(is_array($extra['legend'] ?? null) ? $extra['legend'] : [], 'is_string'));
/** @var array{show: bool, value: string, count: string} $rating */
$rating = $home['rating'];
?>
<section class="hero">
  <div class="hero__text">
    <?= $view->component('section-header', ['number' => $section['number'], 'label' => $section['label'], 'title' => $section['title'], 'highlight' => $section['highlight'] !== '' ? $section['highlight'] : null, 'level' => 'h1', 'wrap' => false, 'intro' => $section['intro'] !== '' ? $section['intro'] : null, 'introClass' => 'hero__lead']) ?>

    <div class="hero__cta">
      <?= $view->component('button', ['label' => $str('cta'), 'href' => (string) $home['bookHref'], 'icon' => 'fa-solid fa-arrow-right']) ?>

<?php if (is_string($home['phoneHref'] ?? null)): ?>
      <?= $view->component('button', ['label' => $str('call'), 'variant' => 'ghost', 'href' => $home['phoneHref'], 'icon' => 'fa-solid fa-phone', 'iconPosition' => 'start', 'iconClass' => 'ic-accent']) ?>

<?php endif; ?>
    </div>
<?php if ($rating['show']): ?>
    <div class="hero__rating">
      <?= $view->component('star-rating', ['rating' => 5.0, 'size' => '16']) ?>

      <span class="hero__rating-text"><strong><?= e($rating['value']) ?></strong> <?= e(str_replace(':count', $rating['count'], $str('rating'))) ?></span>
    </div>
<?php endif; ?>
  </div>
  <div class="hero__art">
    <div class="hero__caption"><?= e($str('caption')) ?></div>
    <?= $view->component('blueprint', ['variant' => 'desktop']) ?>

  </div>
</section>

<section class="blueprint-m" aria-label="<?= e_attr($str('schematic')) ?>">
  <?= $view->component('blueprint', ['variant' => 'mobile']) ?>

<?php if ($legend !== []): ?>
  <?= $view->component('chips', ['items' => $legend]) ?>

<?php endif; ?>
</section>
