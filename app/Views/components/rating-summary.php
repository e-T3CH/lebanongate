<?php
/**
 * Google rating summary: badge (value, stars, count, "Read all" link) or the compact mobile variant.
 * Parameters: Components::SPECS['rating-summary'].
 *
 * @var \Gate\Core\View $view
 * @var array{value: string, count: string, rating: float|int, variant: string, linkHref: ?string, linkLabel: ?string} $p
 */
?>
<?php if ($p['variant'] === 'badge'): ?>
<div class="rating-badge">
      <span class="rating-badge__value"><?= e($p['value']) ?></span>
      <div class="rating-badge__col"><?= $view->component('star-rating', ['rating' => (float) $p['rating'], 'size' => '16']) ?><span class="rating-badge__count"><?= e($p['count']) ?></span></div>
<?php if ($p['linkHref'] !== null && $p['linkLabel'] !== null): ?>
      <a class="rating-badge__link" href="<?= e_url($p['linkHref']) ?>"><?= e($p['linkLabel']) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'size' => '14', 'class' => 'ic-ne']) ?></a>
<?php endif; ?>
    </div>
<?php else: ?>
<div class="rating-m"><span class="rating-m__value"><?= e($p['value']) ?></span><span class="rating-m__col"><?= $view->component('star-rating', ['rating' => (float) $p['rating'], 'size' => '14']) ?><span class="rating-m__count"><?= e($p['count']) ?></span></span></div>
<?php endif; ?>
