<?php
/**
 * Page banner: breadcrumb, label, title and intro over the page image (or the brand gradient).
 *
 * @var \Gate\Core\View $view
 * @var string $title
 * @var string $intro
 * @var string $label
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array{url: string, alt: string, width: int, height: int}|null $image
 */
?>
<section class="page-hero<?= $image !== null ? ' page-hero--image' : '' ?>">
<?php if ($image !== null): ?>
  <img class="page-hero__img" src="<?= e_url($image['url']) ?>" alt="" width="<?= (int) $image['width'] ?>" height="<?= (int) $image['height'] ?>" fetchpriority="high">
<?php endif; ?>
  <div class="wrap page-hero__inner">
<?php if ($breadcrumbs !== []): ?>
    <nav class="crumbs" aria-label="<?= e_attr($view->t('site.breadcrumbs.label')) ?>">
      <ol>
<?php foreach ($breadcrumbs as $i => $crumb): ?>
        <li><?php if (isset($crumb['href'])): ?><a href="<?= e_url($crumb['href']) ?>"><?= e($crumb['label']) ?></a><?php else: ?><span aria-current="page"><?= e($crumb['label']) ?></span><?php endif; ?></li>
<?php endforeach; ?>
      </ol>
    </nav>
<?php endif; ?>
<?php if ($label !== ''): ?>
    <p class="eyebrow eyebrow--light"><?= e($label) ?></p>
<?php endif; ?>
    <h1 class="page-hero__title"><?= e($title) ?></h1>
<?php if ($intro !== ''): ?>
    <p class="page-hero__intro"><?= nl2br(e($intro), false) ?></p>
<?php endif; ?>
  </div>
</section>
