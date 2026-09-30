<?php
/**
 * Grid of expertise cards (home section, expertise page, "other areas").
 *
 * @var \Gate\Core\View $view
 * @var list<array{id: int, key: string, icon: string, title: string, summary: string, href: string}> $areas
 * @var string $heading
 */
$heading = ($heading ?? 'h3') === 'h2' ? 'h2' : 'h3';
?>
<div class="grid grid--3" data-stagger>
<?php foreach ($areas as $area): ?>
  <article class="xp-card reveal">
    <span class="xp-card__icon"><?= svg_icon($area['icon']) ?></span>
    <<?= $heading ?> class="xp-card__title"><a href="<?= e_url($area['href']) ?>"><?= e($area['title']) ?></a></<?= $heading ?>>
<?php if ($area['summary'] !== ''): ?>
    <p><?= e($area['summary']) ?></p>
<?php endif; ?>
    <span class="link-more" aria-hidden="true"><?= e($view->t('site.read_more')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></span>
  </article>
<?php endforeach; ?>
</div>
