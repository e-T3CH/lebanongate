<?php
/**
 * Status pill: neutral, new, confirmed, in diagnosis, quoted, danger; optional status dot or icon.
 * Parameters: Components::SPECS['status-pill'].
 *
 * @var \Gate\Core\View $view
 * @var array{label: string, tone: string, dot: bool, icon: ?string, class: string, attrs: array<string, string|int|bool|null>} $p
 */

use Gate\Core\Props;

$lead = $p['dot'] ? '<span class="pill__dot"></span>' : ($p['icon'] !== null ? $view->component('icon', ['icon' => $p['icon'], 'size' => '13']) . ' ' : '');
?>
<span class="<?= e_attr(Props::classes('pill pill-' . $p['tone'], $p['class'])) ?>"<?= Props::attrs($p['attrs']) ?>><?= $lead ?><?= e($p['label']) ?></span>
