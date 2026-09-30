<?php
/**
 * Dropdown: custom listbox (button + role="listbox" panel, keyboard support in ui.js) with a native <select>
 * fallback. The native select carries the form value: it is shown when JavaScript is off and hidden (but still
 * submitted) when <html> has the .js class; ui.js keeps it in sync with the listbox.
 * Option types: plain label, language code + label, or stars (rating filter).
 * Parameters: Components::SPECS['select'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{name: string, options: list<array{value: string, label: string, code?: string, stars?: int, suffix?: string}>, label: ?string, ariaLabel: ?string, panelLabel: ?string, id: ?string, value: ?string, error: ?string, hint: ?string, size: string, panel: string, leadIcon: ?string, check: string, autosave: bool, toast: ?string, open: bool, required: bool, disabled: bool, class: string, attrs: array<string, string|int|bool|null>} $p
 */

use BMMatic\Core\Props;

$options = [];
foreach ($p['options'] as $o) {
    if (!is_array($o) || !is_string($o['value'] ?? null) || !is_string($o['label'] ?? null)) {
        throw new \InvalidArgumentException('select: every option needs a string value and label');
    }
    $options[] = $o;
}
if ($options === []) {
    throw new \InvalidArgumentException('select: at least one option is required');
}
$selectedIndex = 0;
foreach ($options as $i => $o) {
    if ($o['value'] === $p['value']) {
        $selectedIndex = $i;
    }
}
$id = $p['id'];
$labelId = $id !== null && $p['label'] !== null ? $id . '-label' : null;
$small = $p['size'] === 'sm';
$chevron = $view->component('icon', ['icon' => 'fa-solid fa-chevron-down', 'size' => $small ? '14' : '16', 'class' => 'ic-chev']);
$display = '<span data-select-value>' . e($options[$selectedIndex]['label']) . '</span>';
if ($p['leadIcon'] !== null) {
    $display = '<span class="sel__lead">' . $view->component('icon', ['icon' => $p['leadIcon']]) . ' ' . $display . '</span>';
}
$panelClass = ['below' => 'dd m-pop dd--below', 'full' => 'dd dd--full m-pop', 'rating' => 'dd dd--rating m-pop'][$p['panel']];
$panelName = $labelId !== null ? ' aria-labelledby="' . e_attr($labelId) . '"' : ' aria-label="' . e_attr($p['panelLabel'] ?? $p['ariaLabel'] ?? '') . '"';
$described = array_values(array_filter([$p['hint'] !== null && $id !== null ? $id . '-hint' : null, $p['error'] !== null && $id !== null ? $id . '-error' : null]));

$listItems = '';
$nativeItems = '';
foreach ($options as $i => $o) {
    $selected = $i === $selectedIndex;
    $check = $selected ? (string) $view->component('icon', ['icon' => 'fa-solid fa-check', 'size' => $p['check'] === 'inline' ? '14' : '16', 'class' => $p['check'] === 'inline' ? 'dd__chk-inline' : 'dd__chk']) : '';
    if (isset($o['stars']) && is_int($o['stars'])) {
        $content = $view->component('star-rating', ['rating' => (float) $o['stars'], 'size' => '14', 'variant' => 'admin', 'wrap' => false])
            . (isset($o['suffix']) && is_string($o['suffix']) ? ' ' . e($o['suffix']) : '');
        $extra = ' aria-label="' . e_attr($o['label']) . '"';
    } elseif (isset($o['code']) && is_string($o['code'])) {
        $content = '<span class="code-sm">' . e($o['code']) . '</span><span class="dd__grow">' . e($o['label']) . '</span>' . $check;
        $extra = '';
    } elseif ($p['check'] === 'inline') {
        $content = e($o['label']) . ($check !== '' ? ' ' . $check : '');
        $extra = '';
    } else {
        $content = '<span class="dd__grow">' . e($o['label']) . '</span>' . $check;
        $extra = '';
    }
    $listItems .= '<button type="button" role="option" aria-selected="' . ($selected ? 'true' : 'false') . '" data-value="' . e_attr($o['value']) . '" data-label="' . e_attr($o['label']) . '"' . $extra . '>' . $content . '</button>';
    $nativeItems .= '<option value="' . e_attr($o['value']) . '"' . ($selected ? ' selected' : '') . '>' . e($o['label']) . '</option>';
}

$wrapAttrs = ' data-select' . ($p['autosave'] ? ' data-autosave' : '') . ($p['toast'] !== null ? ' data-toast="' . e_attr($p['toast']) . '"' : '') . Props::attrs($p['attrs']);
$buttonAttrs = ($id !== null ? ' id="' . e_attr($id) . '"' : '')
    . ' class="' . ($small ? 'sel sel--13' : 'sel') . '" type="button"'
    . ($labelId === null && $p['ariaLabel'] !== null ? ' aria-label="' . e_attr($p['ariaLabel']) . '"' : '')
    . ' aria-haspopup="listbox" aria-expanded="' . ($p['open'] ? 'true' : 'false') . '"'
    . ($labelId !== null ? ' aria-labelledby="' . e_attr($labelId . ' ' . $id) . '"' : '')
    . ($p['error'] !== null ? ' aria-invalid="true"' : '')
    . ($described !== [] ? ' aria-describedby="' . e_attr(implode(' ', $described)) . '"' : '')
    . ($p['disabled'] ? ' disabled' : '');
$nativeAttrs = ' class="sel-native" name="' . e_attr($p['name']) . '"'
    . ($id !== null ? ' id="' . e_attr($id) . '-native"' : '')
    . ($labelId !== null ? ' aria-labelledby="' . e_attr($labelId) . '"' : ' aria-label="' . e_attr($p['ariaLabel'] ?? '') . '"')
    . ($p['required'] ? ' required' : '')
    . ($p['disabled'] ? ' disabled' : '');
?>
<div class="<?= e_attr(Props::classes('ul dd-wrap', $p['error'] !== null ? 'has-error' : null, $p['disabled'] ? 'is-disabled' : null, $p['class'])) ?>"<?= $wrapAttrs ?>>
<?php if ($labelId !== null): ?>
  <label id="<?= e_attr($labelId) ?>" for="<?= e_attr((string) $id) ?>"><?= e((string) $p['label']) ?></label>
<?php endif; ?>
  <button<?= $buttonAttrs ?>><?= $display ?> <?= $chevron ?></button>
  <select<?= $nativeAttrs ?>><?= $nativeItems ?></select>
  <div class="<?= e_attr(Props::classes($panelClass, $p['open'] ? 'is-open' : null)) ?>" role="listbox" tabindex="-1"<?= $panelName ?>><?= $listItems ?></div>
<?php if ($p['hint'] !== null): ?>
  <span class="muted form-hint"<?= $id !== null ? ' id="' . e_attr($id) . '-hint"' : '' ?>><?= e($p['hint']) ?></span>
<?php endif; ?>
<?php if ($p['error'] !== null): ?>
  <?= $view->component('form-error', ['message' => $p['error'], 'id' => $id !== null ? $id . '-error' : null]) ?>

<?php endif; ?>
</div>
