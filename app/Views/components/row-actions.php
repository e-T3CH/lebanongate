<?php
/**
 * Small POST actions inside a table row or card header (resend, cancel, delete…). Each action is its own form with
 * a CSRF token; a destructive one opens the confirm modal (never the browser dialog).
 * Parameters: Components::SPECS['row-actions'].
 *
 * @var \Gate\Core\View $view
 * @var array{actions: list<array<string, mixed>>, class: string} $p
 */

use Gate\Core\Props;

$forms = '';
foreach ($p['actions'] as $action) {
    if (!is_array($action) || !is_string($action['action'] ?? null) || !is_string($action['label'] ?? null)) {
        throw new \InvalidArgumentException('row-actions: action and label are required');
    }
    $tone = ($action['tone'] ?? '') === 'danger' ? 'admin-danger' : 'admin-secondary';
    $icon = is_string($action['icon'] ?? null) ? $action['icon'] : null;
    $hidden = '';
    foreach (is_array($action['fields'] ?? null) ? $action['fields'] : [] as $name => $value) {
        if (is_string($name) && is_scalar($value)) {
            $hidden .= '<input type="hidden" name="' . e_attr($name) . '" value="' . e_attr((string) $value) . '">';
        }
    }
    $confirm = is_string($action['confirm'] ?? null) ? $action['confirm'] : null;
    $attrs = $confirm !== null
        ? ' data-confirm="' . e_attr($confirm) . '"'
            . ' data-confirm-title="' . e_attr(is_string($action['confirmTitle'] ?? null) ? $action['confirmTitle'] : $action['label']) . '"'
            . ' data-confirm-ok="' . e_attr($action['label']) . '"'
            . ($tone === 'admin-danger' ? ' data-confirm-tone="danger"' : '')
        : '';
    $forms .= '<form method="post" action="' . e_url($action['action']) . '"' . $attrs . '>' . $view->csrfField() . $hidden
        . $view->component('button', ['label' => $action['label'], 'variant' => $tone, 'type' => 'submit', 'icon' => $icon, 'iconPosition' => 'start', 'class' => 'btn-row'])
        . '</form>';
}
?>
<div class="<?= e_attr(Props::classes('row-actions', $p['class'])) ?>"><?= $forms ?></div>
