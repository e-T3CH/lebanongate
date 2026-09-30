<?php
/**
 * Toggle switch: a real checkbox (keyboard, forms, no JS needed). A hidden input submits the unchecked value.
 * autosave marks admin toggles that save instantly and confirm with a toast (ui.js).
 * Parameters: Components::SPECS['toggle'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{label: ?string, name: ?string, id: ?string, checked: bool, disabled: bool, required: bool, value: string, uncheckedValue: ?string, autosave: bool, toast: ?string, class: string, inputClass: string, attrs: array<string, string|int|bool|null>} $p
 */

use BMMatic\Core\Props;

$hidden = $p['name'] !== null && $p['uncheckedValue'] !== null ? '<input type="hidden" name="' . e_attr($p['name']) . '" value="' . e_attr($p['uncheckedValue']) . '">' : '';
$attrs = ($p['inputClass'] !== '' ? ' class="' . e_attr($p['inputClass']) . '"' : '')
    . ($p['id'] !== null ? ' id="' . e_attr($p['id']) . '"' : '')
    . ($p['name'] !== null ? ' name="' . e_attr($p['name']) . '" value="' . e_attr($p['value']) . '"' : '')
    . ($p['label'] !== null ? ' aria-label="' . e_attr($p['label']) . '"' : '')
    . ($p['autosave'] ? ' data-autosave' : '')
    . ($p['toast'] !== null ? ' data-toast="' . e_attr($p['toast']) . '"' : '')
    . ($p['checked'] ? ' checked' : '')
    . ($p['disabled'] ? ' disabled' : '')
    . ($p['required'] ? ' required' : '')
    . Props::attrs($p['attrs']);
?>
<span class="<?= e_attr(Props::classes('tg', $p['disabled'] ? 'is-disabled' : null, $p['class'])) ?>"><?= $hidden ?><input type="checkbox"<?= $attrs ?>><span class="tr"></span></span>
