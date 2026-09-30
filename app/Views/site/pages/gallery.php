<?php
/**
 * Gallery: albums as picture cards (year filter, pagination).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var list<array<string, mixed>> $cards
 * @var array{total: int, page: int, pages: int} $result
 * @var list<array<string, mixed>> $filters
 * @var array<string, mixed> $active
 * @var \Closure(int): string $pageUrl
 * @var string $clearHref
 */
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section section--list">
  <div class="wrap">
    <?= $view->render('site/parts/filters', ['filters' => $filters, 'anyActive' => $active !== [], 'clearHref' => $clearHref, 'total' => $result['total']]) ?>
<?php if ($cards === []): ?>
    <div class="empty">
      <?= svg_icon('image', 'ic empty__icon') ?>
      <h2 class="h3"><?= e($view->t('site.list.empty_title')) ?></h2>
      <p><?= e($view->t('site.list.empty_album')) ?></p>
    </div>
<?php else: ?>
    <div class="albums" data-stagger>
<?php foreach ($cards as $card): ?>
      <a class="album reveal" href="<?= e_url($card['href']) ?>">
<?php if ($card['cover'] !== null): ?>
        <img src="<?= e_url($card['cover']['url']) ?>" alt="" width="<?= (int) $card['cover']['width'] ?>" height="<?= (int) $card['cover']['height'] ?>" loading="lazy" decoding="async">
<?php else: ?>
        <div class="ph" aria-hidden="true"><?= svg_icon('image', 'ph__icon') ?></div>
<?php endif; ?>
        <span class="album__text"><b><?= e($card['title']) ?></b><small><?= e($card['date']) ?><?= $card['meta'] !== [] ? ' · ' . e($card['meta'][0]['text']) : '' ?></small></span>
      </a>
<?php endforeach; ?>
    </div>
    <?= $view->render('site/parts/pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $pageUrl]) ?>
<?php endif; ?>
  </div>
</section>
