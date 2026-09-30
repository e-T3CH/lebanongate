<?php
/**
 * Hero blueprint: the approved transmission schematic SVG (drawn, then looping gently through motion.js).
 * The SVG files are static application assets extracted from the approved mock-ups by tools/assets/build.mjs;
 * only the translated accessible label is inserted.
 * Parameters: Components::SPECS['blueprint'].
 *
 * @var \Gate\Core\View $view
 * @var array{variant: string} $p
 */

$svg = file_get_contents(__DIR__ . '/svg/blueprint-' . $p['variant'] . '.svg');
if ($svg === false) {
    throw new \RuntimeException('Blueprint SVG missing: ' . $p['variant']);
}
echo str_replace('{{label}}', e_attr($view->t('ui.blueprint.label')), $svg);
