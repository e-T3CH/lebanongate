<?php
/**
 * Underlined textarea with label, hint and error message.
 * Parameters: Components::SPECS['textarea'].
 *
 * @var \Gate\Core\View $view
 * @var array{label: string, name: ?string, id: ?string, value: ?string, rows: int, placeholder: ?string, error: ?string, hint: ?string, maxlength: ?int, required: bool, disabled: bool, class: string, attrs: array<string, string|int|bool|null>} $p
 */

use Gate\Core\Props;

$id = $p['id'] ?? ($p['name'] !== null ? 'f-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $p['name']) : 'f-textarea');
$described = array_values(array_filter([$p['hint'] !== null ? $id . '-hint' : null, $p['error'] !== null ? $id . '-error' : null]));
$attrs = ' id="' . e_attr($id) . '"'
    . ($p['name'] !== null ? ' name="' . e_attr($p['name']) . '"' : '')
    . ' rows="' . max(1, $p['rows']) . '"'
    . ($p['placeholder'] !== null ? ' placeholder="' . e_attr($p['placeholder']) . '"' : '')
    . ($p['maxlength'] !== null ? ' maxlength="' . $p['maxlength'] . '"' : '')
    . ($p['required'] ? ' required' : '')
    . ($p['disabled'] ? ' disabled' : '')
    . ($p['error'] !== null ? ' aria-invalid="true"' : '')
    . ($described !== [] ? ' aria-describedby="' . e_attr(implode(' ', $described)) . '"' : '')
    . Props::attrs($p['attrs']);
?>
<div class="<?= e_attr(Props::classes('ul', $p['error'] !== null ? 'has-error' : null, $p['disabled'] ? 'is-disabled' : null, $p['class'])) ?>"><label for="<?= e_attr($id) ?>"><?= e($p['label']) ?></label><textarea<?= $attrs ?>><?= e($p['value'] ?? '') ?></textarea><?php if ($p['hint'] !== null): ?><span class="muted form-hint" id="<?= e_attr($id) ?>-hint"><?= e($p['hint']) ?></span><?php endif; ?><?php if ($p['error'] !== null): ?><?= $view->component('form-error', ['message' => $p['error'], 'id' => $id . '-error']) ?><?php endif; ?></div>
