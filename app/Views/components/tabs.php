<?php
/**
 * Tabs: link tabs with an underline on the active one (aria-current="page"); optional count "All (128)".
 * Parameters: Components::SPECS['tabs'].
 *
 * @var \Gate\Core\View $view
 * @var array{items: list<array{label: string, href: string, active?: bool, count?: int}>, label: string, variant: string, class: string} $p
 */

use Gate\Core\Props;

$links = '';
foreach ($p['items'] as $item) {
    if (!is_array($item) || !is_string($item['label'] ?? null) || !is_string($item['href'] ?? null)) {
        throw new \InvalidArgumentException('tabs: label and href are required');
    }
    $active = ($item['active'] ?? false) === true;
    $text = $item['label'] . (isset($item['count']) && is_int($item['count']) ? ' (' . $item['count'] . ')' : '');
    $links .= '<a' . ($active ? ' class="on"' : '') . ' href="' . e_url($item['href']) . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($text) . '</a>';
}
?>
<nav class="<?= e_attr(Props::classes('tabs tabs--' . $p['variant'], $p['class'])) ?>" aria-label="<?= e_attr($p['label']) ?>"><?= $links ?></nav>
