<?php
/**
 * Progress bar with percentage. The width comes from a .pct-N class (0–100), never an inline style.
 * With a label it is exposed as a progressbar; without one it is presentational next to visible text.
 * Parameters: Components::SPECS['progress'].
 *
 * @var \Gate\Core\View $view
 * @var array{value: int, label: ?string, class: string} $p
 */

use Gate\Core\Props;

$value = max(0, min(100, $p['value']));
$a11y = $p['label'] !== null ? ' role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $value . '" aria-label="' . e_attr($p['label']) . '"' : '';
?>
<span class="<?= e_attr(Props::classes('progress', $p['class'])) ?>"<?= $a11y ?>><span class="progress__bar"><span class="progress__fill pct-<?= $value ?>"></span></span><span class="progress__pct"><?= e($view->t('ui.progress.value', ['value' => $value])) ?></span></span>
