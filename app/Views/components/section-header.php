<?php
/**
 * Section header: label + heading with an accent highlight (<em>) + optional intro paragraph.
 * With wrap=false the parts are printed without the wrapper (inside a panel or the hero text column).
 * Parameters: Components::SPECS['section-header'].
 *
 * @var \Gate\Core\View $view
 * @var array{label: string, title: string, number: ?string, highlight: ?string, intro: ?string, level: string, wrap: bool, class: string, introClass: string} $p
 */

$label = $view->component('section-label', ['text' => $p['label'], 'number' => $p['number']]);
$heading = '<' . $p['level'] . '>' . e($p['title']) . ($p['highlight'] !== null ? ' <em>' . e($p['highlight']) . '</em>' : '') . '</' . $p['level'] . '>';
$intro = $p['intro'] !== null ? '<p' . ($p['introClass'] !== '' ? ' class="' . e_attr($p['introClass']) . '"' : '') . '>' . e($p['intro']) . '</p>' : '';
$indent = $p['wrap'] ? "\n  " : "\n";
$parts = $label . $indent . $heading . ($intro !== '' ? $indent . $intro : '');
?>
<?php if ($p['wrap']): ?>
<div class="<?= e_attr($p['class']) ?>">
  <?= $parts ?>

</div>
<?php else: ?>
<?= $parts ?>
<?php endif; ?>
