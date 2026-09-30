<?php
/**
 * Home section "reviews" (also used on the reviews page): header, Google rating badge (compact below 768px), review
 * cards (a swipeable carousel below 768px) or an empty state until reviews are imported.
 *
 * @var \Gate\Core\View $view
 * @var array{number: ?string, label: string, title: string, highlight: string, extra: array<string, mixed>} $section
 * @var array<string, mixed> $home
 */
$extra = $section['extra'];
$str = static fn (string $k): string => is_string($extra[$k] ?? null) ? $extra[$k] : '';
/** @var array{show: bool, value: string, count: string, googleUrl: ?string} $rating */
$rating = $home['rating'];
/** @var list<array{initial: string, name: string, date: string, text: string, rating: float, photo?: ?string}> $reviews */
$reviews = $home['reviews'];
$count = str_replace(':count', $rating['count'], $str('count'));
$showHeader = !isset($home['reviewsHeader']) || $home['reviewsHeader'] === true;
?>
<section class="sec sec--reviews"<?= $showHeader ? '' : ' aria-label="' . e_attr($section['label']) . '"' ?>>
<?php if ($showHeader): ?>
  <div class="sec__row">
    <?= $view->component('section-header', ['number' => $section['number'], 'label' => $section['label'], 'title' => $section['title'], 'highlight' => $section['highlight'] !== '' ? $section['highlight'] : null]) ?>

<?php if ($rating['show']): ?>
    <?= $view->component('rating-summary', ['value' => $rating['value'], 'count' => $count, 'linkHref' => $rating['googleUrl'], 'linkLabel' => $rating['googleUrl'] !== null ? $str('read_all') : null]) ?>

<?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($rating['show']): ?>
  <?= $view->component('rating-summary', ['value' => $rating['value'], 'count' => $count, 'variant' => 'compact']) ?>

<?php endif; ?>
<?php if ($reviews !== []): ?>
  <?php /* tabindex: below 768px this is a horizontal scroll container, which needs keyboard access (WCAG 2.1.1). */ ?>
  <div class="reviews" tabindex="0" role="group" aria-label="<?= e_attr($section['label'] !== '' ? $section['label'] : $view->t('site.reviews')) ?>">
<?php foreach ($reviews as $review): ?>
    <?= $view->component('review-card', ['initial' => $review['initial'], 'name' => $review['name'], 'date' => $review['date'], 'text' => $review['text'], 'rating' => $review['rating'], 'photo' => is_string($review['photo'] ?? null) ? $review['photo'] : null, 'starSize' => '15']) ?>

<?php endforeach; ?>
  </div>
<?php if (count($reviews) > 1): ?>
  <div class="carousel-dots" aria-hidden="true"><?php foreach ($reviews as $i => $review): ?><span<?= $i === 0 ? ' class="on"' : '' ?>></span><?php endforeach; ?></div>
<?php endif; ?>
<?php else: ?>
  <div class="reviews-empty"><?= $view->component('empty-state', ['variant' => 'public', 'icon' => 'fa-regular fa-star', 'title' => $str('empty_title'), 'text' => $str('empty_text'), 'action' => $rating['googleUrl'] !== null ? $view->component('button', ['label' => $str('google'), 'variant' => 'ghost', 'href' => $rating['googleUrl'], 'icon' => 'fa-solid fa-arrow-right', 'iconClass' => 'ic-ne', 'attrs' => ['rel' => 'noopener', 'target' => '_blank']]) : null]) ?></div>
<?php endif; ?>
</section>
