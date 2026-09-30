<?php
/**
 * Reviews: heading and the reviews section (Google rating, the approved review cards, or an empty state).
 *
 * @var \BMMatic\Core\View $view
 * @var array<string, mixed> $page
 * @var array<string, mixed> $home
 * @var array<string, mixed> $reviewsSection
 * @var array{title: string, text: string, bookHref: string, phoneHref: ?string} $cta
 */
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => (string) $page['title'], 'highlight' => (string) $page['highlight'], 'lead' => (string) $page['intro'], 'breadcrumbs' => $page['breadcrumbs']]) ?>

<?= $view->render('site/sections/reviews', ['section' => $reviewsSection, 'home' => ['reviewsHeader' => false] + $home]) ?>
<section class="sec sec--last">
  <?= $view->component('cta-band', ['title' => $cta['title'], 'text' => $cta['text'], 'primaryLabel' => $view->t('site.cta.book'), 'primaryHref' => $cta['bookHref'], 'secondaryLabel' => $cta['phoneHref'] !== null ? $view->t('site.cta.call') : null, 'secondaryHref' => $cta['phoneHref']]) ?>

</section>
