<?php
/**
 * Filter chips above a list: one group per facet; every chip is a link that toggles its value (works without JS).
 *
 * @var \Gate\Core\View $view
 * @var list<array{name: string, label: string, options: list<array{label: string, href: string, active: bool}>}> $filters
 * @var bool $anyActive
 * @var string $clearHref
 * @var int $total
 */
if ($filters === []) {
    return;
}
?>
<div class="filters" role="group" aria-label="<?= e_attr($view->t('site.filters.label')) ?>">
<?php foreach ($filters as $group): ?>
  <div class="filters__group">
    <span class="filters__label"><?= e($group['label']) ?></span>
    <ul class="filters__chips">
<?php foreach ($group['options'] as $option): ?>
      <li><a class="fchip<?= $option['active'] ? ' is-active' : '' ?>" href="<?= e_url($option['href']) ?>"<?= $option['active'] ? ' aria-current="true"' : '' ?>><?= $option['active'] ? svg_icon('check', 'ic ic--check') : '' ?><?= e($option['label']) ?></a></li>
<?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
  <p class="filters__result" role="status"><?= e($view->t($total === 1 ? 'site.filters.one_result' : 'site.filters.results', ['n' => $total])) ?><?php if ($anyActive): ?> · <a href="<?= e_url($clearHref) ?>"><?= e($view->t('site.filters.clear')) ?></a><?php endif; ?></p>
</div>
