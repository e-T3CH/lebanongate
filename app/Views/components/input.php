<?php
/**
 * Underlined input: uppercase label, value on an underline; hint and error message linked with aria-describedby.
 * Passwords never echo their value and get a show/hide button (reveal; the button only appears with JavaScript, which
 * switches the field between hidden and readable text and hides it again before the form is sent).
 * suffix renders a unit after the value ("minutes"). color puts a colour picker before a colour code: the text field
 * keeps the value that is saved (#RRGGBB, #RGB, #RRGGBBAA or rgb()/rgba()); the picker has no name and, with JavaScript,
 * writes its choice into the field in the same format, keeping any transparency.
 * Parameters: Components::SPECS['input'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{label: ?string, name: ?string, id: ?string, type: string, value: ?string, placeholder: ?string, autocomplete: ?string, inputmode: ?string, ariaLabel: ?string, error: ?string, hint: ?string, suffix: ?string, maxlength: ?int, required: bool, disabled: bool, readonly: bool, autofocus: bool, reveal: bool, color: bool, class: string, inputClass: string, attrs: array<string, string|int|bool|null>} $p
 */

use BMMatic\Core\Props;

$id = $p['id'] ?? ($p['name'] !== null ? 'f-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $p['name']) : null);
$described = [];
if ($p['hint'] !== null && $id !== null) {
    $described[] = $id . '-hint';
}
if ($p['error'] !== null && $id !== null) {
    $described[] = $id . '-error';
}
$field = '<input'
    . ($p['inputClass'] !== '' ? ' class="' . e_attr($p['inputClass']) . '"' : '')
    . ($id !== null ? ' id="' . e_attr($id) . '"' : '')
    . ($p['name'] !== null ? ' name="' . e_attr($p['name']) . '"' : '')
    . ' type="' . e_attr($p['type']) . '"'
    . ($p['value'] !== null && $p['type'] !== 'password' ? ' value="' . e_attr($p['value']) . '"' : '')
    . ($p['placeholder'] !== null ? ' placeholder="' . e_attr($p['placeholder']) . '"' : '')
    . ($p['autocomplete'] !== null ? ' autocomplete="' . e_attr($p['autocomplete']) . '"' : '')
    . ($p['inputmode'] !== null ? ' inputmode="' . e_attr($p['inputmode']) . '"' : '')
    . ($p['ariaLabel'] !== null ? ' aria-label="' . e_attr($p['ariaLabel']) . '"' : '')
    . ($p['maxlength'] !== null ? ' maxlength="' . $p['maxlength'] . '"' : '')
    . ($p['required'] ? ' required' : '')
    . ($p['disabled'] ? ' disabled' : '')
    . ($p['readonly'] ? ' readonly' : '')
    . ($p['autofocus'] ? ' autofocus' : '')
    . ($p['error'] !== null ? ' aria-invalid="true"' : '')
    . ($described !== [] ? ' aria-describedby="' . e_attr(implode(' ', $described)) . '"' : '')
    . Props::attrs($p['attrs']) . '>';
if ($p['type'] === 'password' && $p['reveal'] && $id !== null && !$p['disabled'] && !$p['readonly']) {
    $field = '<span class="pw-field">' . $field
        . '<button type="button" class="pw-toggle" data-pw-toggle aria-controls="' . e_attr($id) . '" aria-pressed="false" aria-label="' . e_attr($view->t('ui.password.show')) . '">'
        . '<span class="pw-toggle__show">' . $view->component('icon', ['icon' => 'fa-regular fa-eye', 'size' => '16']) . '</span>'
        . '<span class="pw-toggle__hide">' . $view->component('icon', ['icon' => 'fa-regular fa-eye-slash', 'size' => '16']) . '</span>'
        . '</button></span>';
}
if ($p['color'] && $id !== null) {
    // The picker shows the colour without its transparency: #rrggbb from any accepted notation.
    $value = trim((string) $p['value']);
    $hex = '#000000';
    if (preg_match('/^#([0-9a-f]{3})$/i', $value, $m) === 1) {
        $hex = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
    } elseif (preg_match('/^#([0-9a-f]{6})(?:[0-9a-f]{2})?$/i', $value, $m) === 1) {
        $hex = '#' . $m[1];
    } elseif (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $value, $m) === 1) {
        $hex = sprintf('#%02x%02x%02x', min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
    }
    $field = '<span class="color-field"><input type="color" class="color-field__pick" id="' . e_attr($id) . '-pick" value="' . e_attr(strtolower($hex)) . '"'
        . ' data-color-pick="' . e_attr($id) . '" aria-label="' . e_attr($view->t('ui.color.pick', ['name' => $p['label'] ?? $p['name'] ?? ''])) . '"'
        . ($p['disabled'] || $p['readonly'] ? ' disabled' : '') . '>' . $field . '</span>';
}
if ($p['suffix'] !== null) {
    $field = '<span class="suffix-field">' . $field . '<span class="suffix-field__unit">' . e($p['suffix']) . '</span></span>';
}
?>
<div class="<?= e_attr(Props::classes('ul', $p['error'] !== null ? 'has-error' : null, $p['disabled'] ? 'is-disabled' : null, $p['class'])) ?>"><?php if ($p['label'] !== null): ?><label<?= $id !== null ? ' for="' . e_attr($id) . '"' : '' ?>><?= e($p['label']) ?></label><?php endif; ?><?= $field ?><?php if ($p['hint'] !== null): ?><span class="muted form-hint"<?= $id !== null ? ' id="' . e_attr($id) . '-hint"' : '' ?>><?= e($p['hint']) ?></span><?php endif; ?><?php if ($p['error'] !== null): ?><?= $view->component('form-error', ['message' => $p['error'], 'id' => $id !== null ? $id . '-error' : null]) ?><?php endif; ?></div>
