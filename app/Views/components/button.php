<?php
/**
 * Button: public primary (.btn-p) / ghost (.btn-g), admin primary (.abtn-p) / secondary (.abtn-s) / danger (.abtn-d).
 * A link when href is set, otherwise a <button>. States: disabled, loading (spinner, aria-busy, not clickable).
 * Parameters: Components::SPECS['button'].
 *
 * @var \Gate\Core\View $view
 * @var array{label: string, variant: string, href: ?string, type: string, icon: ?string, iconPosition: string, iconClass: string, iconSize: string, class: string, id: ?string, name: ?string, value: ?string, ariaLabel: ?string, disabled: bool, loading: bool, iconOnly: bool, attrs: array<string, string|int|bool|null>} $p
 */

use Gate\Core\Props;

$variants = ['primary' => 'btn btn-p', 'ghost' => 'btn btn-g', 'admin-primary' => 'abtn abtn-p', 'admin-secondary' => 'abtn abtn-s', 'admin-danger' => 'abtn abtn-d'];
$icon = $p['loading'] ? 'fa-solid fa-spinner' : $p['icon'];
$iconHtml = $icon !== null ? (string) $view->component('icon', ['icon' => $icon, 'size' => $p['iconSize'], 'class' => Props::classes($p['iconClass'], $p['loading'] ? 'ic-spin' : null)]) : '';
// iconOnly: the label stays in the markup for screen readers, but only the icon is drawn (compact row actions).
$label = $p['iconOnly'] && $iconHtml !== '' ? '<span class="visually-hidden">' . e($p['label']) . '</span>' : e($p['label']);
$inner = $iconHtml === '' ? $label : ($p['iconPosition'] === 'start' || $p['loading'] ? $iconHtml . ' ' . $label : $label . ' ' . $iconHtml);
$class = Props::classes($variants[$p['variant']], $p['class'], $p['loading'] ? 'is-loading' : null);
$inactive = $p['disabled'] || $p['loading'];
$common = ($p['id'] !== null ? ' id="' . e_attr($p['id']) . '"' : '')
    . ($p['ariaLabel'] !== null ? ' aria-label="' . e_attr($p['ariaLabel']) . '"' : '')
    . ($p['loading'] ? ' aria-busy="true"' : '')
    . Props::attrs($p['attrs']);
?>
<?php if ($p['href'] !== null): ?>
<a class="<?= e_attr($class) ?>"<?= $inactive ? ' role="link" aria-disabled="true"' : ' href="' . e_url($p['href']) . '"' ?><?= $common ?>><?= $inner ?></a>
<?php else: ?>
<button class="<?= e_attr($class) ?>" type="<?= e_attr($p['type']) ?>"<?= $p['name'] !== null ? ' name="' . e_attr($p['name']) . '"' : '' ?><?= $p['value'] !== null ? ' value="' . e_attr($p['value']) . '"' : '' ?><?= $inactive ? ' disabled' : '' ?><?= $common ?>><?= $inner ?></button>
<?php endif; ?>
