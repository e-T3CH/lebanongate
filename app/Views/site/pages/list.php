<?php
/**
 * List of projects, news or publications: filters, cards, pagination, empty state.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var string $type
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
      <?= svg_icon('info', 'ic empty__icon') ?>
      <h2 class="h3"><?= e($view->t('site.list.empty_title')) ?></h2>
      <p><?= e($view->t($active !== [] ? 'site.list.empty_filtered' : 'site.list.empty_' . $type)) ?></p>
<?php if ($active !== []): ?>
      <a class="btn btn--outline" href="<?= e_url($clearHref) ?>"><?= e($view->t('site.filters.clear')) ?></a>
<?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid grid--3<?= $type === 'publication' ? ' grid--pubs' : '' ?>" data-stagger>
<?php foreach ($cards as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h2']) ?>
<?php endforeach; ?>
    </div>
    <?= $view->render('site/parts/pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $pageUrl]) ?>
<?php endif; ?>
  </div>
</section>
