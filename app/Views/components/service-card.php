<?php
/**
 * Service card (public card): icon tile, number, title, text, "Learn more" (desktop) or arrow (mobile row).
 * Parameters: Components::SPECS['service-card'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{title: string, text: string, icon: string, href: string, number: ?string} $p
 */

use BMMatic\Core\Html;

$arrow = (string) $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'class' => 'ic-ne']);
$content = "\n  " . '<span class="svc__top"><span class="icon-tile">' . $view->component('icon', ['icon' => $p['icon'], 'size' => '24']) . '</span>'
    . ($p['number'] !== null ? '<span class="svc__num">' . e($p['number']) . '</span>' : '') . '</span>'
    . "\n  " . '<span class="svc__body"><span class="svc__title">' . e($p['title']) . '</span><span class="svc__text">' . e($p['text']) . '</span></span>'
    . "\n  " . '<span class="svc__more">' . e($view->t('ui.service.more')) . ' ' . $arrow . '</span>'
    . "\n  " . '<span class="svc__arrow">' . $arrow . '</span>' . "\n";
?>
<?= $view->component('card', ['tag' => 'a', 'href' => $p['href'], 'class' => 'svc', 'content' => Html::trusted($content)]) ?>
