<?php
/**
 * Pagination for the public lists: previous/next and page numbers (with gaps).
 *
 * @var \Gate\Core\View $view
 * @var int $page
 * @var int $pages
 * @var \Closure(int): string $url
 */
if ($pages < 2) {
    return;
}
$numbers = array_values(array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], static fn (int $n): bool => $n >= 1 && $n <= $pages)));
sort($numbers);
?>
<nav class="pager" aria-label="<?= e_attr($view->t('site.pagination.label')) ?>">
  <ul>
<?php if ($page > 1): ?>
    <li><a class="pager__step" href="<?= e_url($url($page - 1)) ?>" rel="prev"><?= svg_icon('chevron', 'ic ic--prev') ?><span><?= e($view->t('site.pagination.previous')) ?></span></a></li>
<?php endif; ?>
<?php $last = 0; foreach ($numbers as $n): ?>
<?php if ($n - $last > 1): ?>
    <li><span class="pager__gap" aria-hidden="true">…</span></li>
<?php endif; ?>
    <li><?php if ($n === $page): ?><span class="pager__num is-current" aria-current="page"><?= $n ?></span><?php else: ?><a class="pager__num" href="<?= e_url($url($n)) ?>" aria-label="<?= e_attr($view->t('site.pagination.page', ['n' => $n])) ?>"><?= $n ?></a><?php endif; ?></li>
<?php $last = $n; endforeach; ?>
<?php if ($page < $pages): ?>
    <li><a class="pager__step" href="<?= e_url($url($page + 1)) ?>" rel="next"><span><?= e($view->t('site.pagination.next')) ?></span><?= svg_icon('chevron', 'ic') ?></a></li>
<?php endif; ?>
  </ul>
</nav>
