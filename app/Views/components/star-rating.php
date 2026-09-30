<?php
/**
 * Star rating: five solid stars, filled #F5B82E; empty stars #D5DEE7 (admin rating column and filter).
 * The rating is rounded to whole stars; the accessible label keeps one decimal.
 * Parameters: Components::SPECS['star-rating'].
 *
 * @var \Gate\Core\View $view
 * @var array{rating: float|int, size: string, variant: string, wrap: bool, class: string} $p
 */

use Gate\Core\Props;

$rating = max(0.0, min(5.0, (float) $p['rating']));
$filled = (int) round($rating);
$admin = $p['variant'] === 'admin';
$icons = '';
for ($i = 1; $i <= 5; $i++) {
    $state = $admin || $filled < 5 ? ($i <= $filled ? 'ic-star-on' : 'ic-star-off') : '';
    $icons .= '<i class="' . e_attr(Props::classes('ic ic-' . $p['size'], 'fa-solid fa-star', $state)) . '" aria-hidden="true"></i>';
}
$decimal = $view->locale() === 'en' ? '.' : ',';
$number = floor($rating) === $rating ? (string) (int) $rating : number_format($rating, 1, $decimal, '');
?>
<?php if ($p['wrap']): ?>
<span class="<?= e_attr(Props::classes('stars', 'stars--' . ($admin ? 'admin' : $p['size']), $p['class'])) ?>" role="img" aria-label="<?= e_attr($view->t('ui.rating.stars', ['rating' => $number])) ?>"><?= $icons ?></span>
<?php else: ?>
<?= $icons ?>
<?php endif; ?>
