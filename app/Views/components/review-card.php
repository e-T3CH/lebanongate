<?php
/**
 * Review card (public card): avatar (the reviewer photo through this site, initials otherwise), name,
 * date "via Google", stars, review text.
 * Parameters: Components::SPECS['review-card'].
 *
 * @var \Gate\Core\View $view
 * @var array{name: string, date: string, text: string, initial: string, photo: ?string, rating: float|int, starSize: string} $p
 */

use Gate\Core\Html;

$avatar = $p['photo'] !== null
    ? '<img class="avatar avatar--photo" src="' . e_url($p['photo']) . '" alt="" width="42" height="42" loading="lazy" decoding="async">'
    : '<span class="avatar" aria-hidden="true">' . e($p['initial']) . '</span>';
$content = "\n  " . '<div class="review__top">'
    . "\n    " . $avatar
    . "\n    " . '<div class="review__who"><span class="review__name">' . e($p['name']) . '</span><span class="review__date">' . e($view->t('ui.review.meta', ['date' => $p['date']])) . '</span></div>'
    . "\n  " . '</div>'
    . "\n  " . $view->component('star-rating', ['rating' => (float) $p['rating'], 'size' => $p['starSize']])
    . "\n  " . '<p class="review__text">' . e($p['text']) . '</p>' . "\n";
?>
<?= $view->component('card', ['class' => 'review', 'content' => Html::trusted($content)]) ?>
