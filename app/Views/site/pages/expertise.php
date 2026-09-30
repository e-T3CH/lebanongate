<?php
/**
 * Our expertise: every area as a card.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var list<array<string, mixed>> $areas
 * @var array<string, mixed> $cta
 */
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section">
  <div class="wrap">
    <?= $view->render('site/parts/expertise-grid', ['areas' => $areas, 'heading' => 'h2']) ?>
  </div>
</section>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
