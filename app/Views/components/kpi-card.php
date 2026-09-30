<?php
/**
 * KPI card (admin dashboard): label, icon tile, large value, optional delta line (up = green, late = orange).
 * Parameters: Components::SPECS['kpi-card'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{label: string, value: string, icon: string, delta: ?string, tone: string, class: string} $p
 */

use BMMatic\Core\Props;

$delta = $p['delta'] !== null
    ? '<span class="' . e_attr(Props::classes('kpi__delta', $p['tone'] !== 'neutral' ? 'kpi__delta--' . $p['tone'] : null)) . '">' . e($p['delta']) . '</span>'
    : '';
?>
<div class="<?= e_attr(Props::classes('acard kpi', $p['class'])) ?>"><div class="between"><span class="muted kpi__label"><?= e($p['label']) ?></span><span class="tile"><?= $view->component('icon', ['icon' => $p['icon'], 'size' => '18']) ?></span></div><span class="kpi__value"><?= e($p['value']) ?></span><?= $delta ?></div>
