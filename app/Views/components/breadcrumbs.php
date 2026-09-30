<?php
/**
 * Breadcrumb trail (nav + ordered list); the last item is the current page (aria-current).
 * Parameters: Components::SPECS['breadcrumbs'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{items: list<array{label: string, href?: ?string}>} $p
 */

$items = '';
$last = count($p['items']) - 1;
foreach ($p['items'] as $i => $item) {
    if (!is_array($item) || !is_string($item['label'] ?? null)) {
        throw new \InvalidArgumentException('breadcrumbs: label is required');
    }
    $href = is_string($item['href'] ?? null) ? $item['href'] : null;
    $items .= $i === $last || $href === null
        ? '<li><span' . ($i === $last ? ' aria-current="page"' : '') . '>' . e($item['label']) . '</span></li>'
        : '<li><a href="' . e_url($href) . '">' . e($item['label']) . '</a></li>';
}
?>
<?php if ($items !== ''): ?>
<nav aria-label="<?= e_attr($view->t('site.breadcrumbs.label')) ?>"><ol class="breadcrumbs"><?= $items ?></ol></nav>
<?php endif; ?>
