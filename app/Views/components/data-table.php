<?php
/**
 * Data table (admin): header row, cells as Html or escaped text, data-label per cell so rows stack as cards
 * below 1024px. A column with hideLabel has a visually hidden header (e.g. row actions).
 * A cell may be ['content' => Html|string, 'class' => 'extra classes'].
 * Parameters: Components::SPECS['data-table'].
 *
 * @var \Gate\Core\View $view
 * @var array{columns: list<array{key: string, label: string, hideLabel?: bool, class?: string}>, rows: list<array<string, mixed>>, caption: ?string, empty: \Gate\Core\Html|string|null, class: string} $p
 */

use Gate\Core\Html;
use Gate\Core\Props;

$columns = [];
foreach ($p['columns'] as $c) {
    if (!is_array($c) || !is_string($c['key'] ?? null) || !is_string($c['label'] ?? null)) {
        throw new \InvalidArgumentException('data-table: every column needs key and label');
    }
    $columns[] = $c;
}
$head = '';
foreach ($columns as $c) {
    $head .= ($c['hideLabel'] ?? false) === true ? '<th><span class="visually-hidden">' . e($c['label']) . '</span></th>' : '<th>' . e($c['label']) . '</th>';
}
$body = '';
foreach ($p['rows'] as $row) {
    if (!is_array($row)) {
        throw new \InvalidArgumentException('data-table: rows must be arrays');
    }
    $cells = '';
    foreach ($columns as $c) {
        $value = $row[$c['key']] ?? '';
        $extra = '';
        if (is_array($value)) {
            $extra = is_string($value['class'] ?? null) ? $value['class'] : '';
            $value = $value['content'] ?? '';
        }
        if (!is_string($value) && !$value instanceof Html) {
            throw new \InvalidArgumentException('data-table: cell ' . $c['key'] . ' must be Html or string');
        }
        if (!Props::matches('classes', $extra) || !Props::matches('classes', $c['class'] ?? '')) {
            throw new \InvalidArgumentException('data-table: invalid cell class');
        }
        $class = Props::classes(is_string($c['class'] ?? null) ? $c['class'] : null, $extra);
        $cells .= '<td' . ($class !== '' ? ' class="' . e_attr($class) . '"' : '') . (($c['hideLabel'] ?? false) === true ? '' : ' data-label="' . e_attr($c['label']) . '"') . '>' . Html::of($value) . '</td>';
    }
    $body .= "\n        <tr>" . $cells . '</tr>';
}
if ($body === '' && $p['empty'] !== null) {
    $body = "\n        <tr><td colspan=\"" . count($columns) . '">' . Html::of($p['empty']) . '</td></tr>';
}
?>
<table class="<?= e_attr(Props::classes('tbl', $p['class'])) ?>"><?php if ($p['caption'] !== null): ?><caption class="visually-hidden"><?= e($p['caption']) ?></caption><?php endif; ?><thead><tr><?= $head ?></tr></thead><tbody><?= $body ?>

      </tbody></table>
