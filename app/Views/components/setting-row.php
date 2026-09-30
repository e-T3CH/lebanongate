<?php
/**
 * Setting row (admin): name and optional description on the left, a control (toggle, select, input) on the right.
 * Parameters: Components::SPECS['setting-row'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{name: string, control: \BMMatic\Core\Html|string, description: ?string, wide: bool, class: string} $p
 */

use BMMatic\Core\Html;
use BMMatic\Core\Props;

$name = '<span class="setting__name">' . e($p['name']) . '</span>';
$left = $p['description'] !== null
    ? '<span class="' . ($p['wide'] ? 'setting setting--wide' : 'setting') . '">' . $name . '<span class="muted">' . e($p['description']) . '</span></span>'
    : $name;
?>
<div class="<?= e_attr(Props::classes('row', $p['class'])) ?>"><?= $left ?><?= Html::of($p['control']) ?></div>
