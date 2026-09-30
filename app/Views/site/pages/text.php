<?php
/**
 * A text page: About's child pages (with a side menu of the sibling pages) and the legal pages.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var string $body
 * @var list<array{label: string, href: string, current: bool}> $siblings
 * @var array<string, mixed> $cta
 */
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section">
  <div class="wrap<?= $siblings !== [] ? ' with-side' : ' narrow' ?>">
<?php if ($siblings !== []): ?>
    <nav class="side-nav reveal" aria-label="<?= e_attr($view->t('site.nav.section')) ?>">
      <ul>
<?php foreach ($siblings as $item): ?>
        <li><a href="<?= e_url($item['href']) ?>"<?= $item['current'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
<?php endforeach; ?>
      </ul>
    </nav>
<?php endif; ?>
    <div class="prose reveal"><?= $body /* RichText::render(): sanitised */ ?></div>
  </div>
</section>
<?php if ($siblings !== []): ?>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
<?php endif; ?>
