<?php
/**
 * Chips: rounded labels (transmission types, blueprint legend).
 * Parameters: Components::SPECS['chips'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{items: list<string>, class: string} $p
 */

use BMMatic\Core\Props;

$chips = '';
foreach ($p['items'] as $item) {
    if (!is_string($item)) {
        throw new \InvalidArgumentException('chips: items must be strings');
    }
    $chips .= '<span class="chip">' . e($item) . '</span>';
}
?>
<div class="<?= e_attr(Props::classes('chips', $p['class'])) ?>"><?= $chips ?></div>
