<?php
/**
 * Pagination: previous / next and page numbers with gaps (first, last and two around the current page).
 * url contains {page}. Nothing is rendered for a single page.
 * Parameters: Components::SPECS['pagination'].
 *
 * @var \Gate\Core\View $view
 * @var array{page: int, pages: int, url: string, class: string} $p
 */

use Gate\Core\Props;

$pages = max(1, $p['pages']);
if ($pages < 2) {
    return;
}
$page = max(1, min($pages, $p['page']));
if (!str_contains($p['url'], '{page}')) {
    throw new \InvalidArgumentException('pagination: url must contain {page}');
}
$href = static fn (int $n): string => e_url(str_replace('{page}', (string) $n, $p['url']));
$show = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages], static fn (int $n): bool => $n >= 1 && $n <= $pages));
sort($show);
$items = '';
$last = 0;
foreach ($show as $n) {
    if ($n - $last > 1) {
        $items .= '<span class="pager__gap" aria-hidden="true">…</span>';
    }
    $items .= $n === $page
        ? '<a class="pager__page is-current" aria-current="page" href="' . $href($n) . '" aria-label="' . e_attr($view->t('ui.pagination.page', ['page' => $n])) . '">' . $n . '</a>'
        : '<a class="pager__page" href="' . $href($n) . '" aria-label="' . e_attr($view->t('ui.pagination.page', ['page' => $n])) . '">' . $n . '</a>';
    $last = $n;
}
$prevIcon = $view->component('icon', ['icon' => 'fa-solid fa-chevron-left', 'size' => '14']);
$nextIcon = $view->component('icon', ['icon' => 'fa-solid fa-chevron-right', 'size' => '14']);
$prev = $page > 1
    ? '<a class="pager__btn" href="' . $href($page - 1) . '" rel="prev" aria-label="' . e_attr($view->t('ui.pagination.previous')) . '">' . $prevIcon . '</a>'
    : '<span class="pager__btn is-disabled" aria-hidden="true">' . $prevIcon . '</span>';
$next = $page < $pages
    ? '<a class="pager__btn" href="' . $href($page + 1) . '" rel="next" aria-label="' . e_attr($view->t('ui.pagination.next')) . '">' . $nextIcon . '</a>'
    : '<span class="pager__btn is-disabled" aria-hidden="true">' . $nextIcon . '</span>';
?>
<nav class="<?= e_attr(Props::classes('pager', $p['class'])) ?>" aria-label="<?= e_attr($view->t('ui.pagination.label')) ?>"><?= $prev ?><?= $items ?><?= $next ?></nav>
