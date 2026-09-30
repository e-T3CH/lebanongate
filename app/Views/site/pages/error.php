<?php
/**
 * Public error page (404, 503 and other errors) in the site layout: status, message, links home and to contact.
 *
 * @var \BMMatic\Core\View $view
 * @var int $status
 * @var string $key site.errors.* key prefix
 * @var string $homeHref
 * @var string|null $contactHref
 * @var string|null $phoneHref
 * @var string|null $reference error log reference (500/503), so a phone call finds the right log entry
 */
$actions = (string) $view->component('button', ['label' => $view->t('site.errors.home'), 'href' => $homeHref, 'icon' => 'fa-solid fa-arrow-right']);
if ($contactHref !== null) {
    $actions .= $view->component('button', ['label' => $view->t('site.errors.contact'), 'variant' => 'ghost', 'href' => $contactHref]);
} elseif ($phoneHref !== null) {
    $actions .= $view->component('button', ['label' => $view->t('site.cta.call'), 'variant' => 'ghost', 'href' => $phoneHref, 'icon' => 'fa-solid fa-phone', 'iconPosition' => 'start', 'iconClass' => 'ic-accent']);
}
?>
<?= $view->component('page-hero', ['label' => (string) $status, 'title' => $view->t('site.errors.' . $key . '_title'), 'lead' => $view->t('site.errors.' . $key . '_text') . (isset($reference) && is_string($reference) ? ' ' . $view->t('site.errors.reference', ['code' => $reference]) : ''), 'actions' => \BMMatic\Core\Html::trusted($actions), 'class' => 'error-page']) ?>
