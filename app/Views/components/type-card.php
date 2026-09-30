<?php
/**
 * Transmission type card (public card without hover lift): name and the brands or models it covers.
 * Parameters: Components::SPECS['type-card'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{title: string, text: ?string} $p
 */

use BMMatic\Core\Html;

// h2: on the transmissions page these cards follow the page H1 directly.
$content = '<h2 class="type__title">' . e($p['title']) . '</h2>' . ($p['text'] !== null && $p['text'] !== '' ? '<p class="type__text">' . e($p['text']) . '</p>' : '');
?>
<?= $view->component('card', ['tag' => 'article', 'class' => 'type', 'content' => Html::trusted($content)]) ?>
