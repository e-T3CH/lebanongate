<?php
/**
 * Transmissions: heading, transmission type cards, call to action.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, description: string}> $types
 * @var array{title: string, text: string, bookHref: string, phoneHref: ?string} $cta
 */
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => (string) $page['title'], 'highlight' => (string) $page['highlight'], 'lead' => (string) $page['intro'], 'breadcrumbs' => $page['breadcrumbs']]) ?>

<section class="sec" aria-label="<?= e_attr((string) $page['nav_label']) ?>">
  <div class="types">
<?php foreach ($types as $type): ?>
    <?= $view->component('type-card', ['title' => $type['label'], 'text' => $type['description']]) ?>

<?php endforeach; ?>
  </div>
</section>
<section class="sec sec--last">
  <?= $view->component('cta-band', ['title' => $cta['title'], 'text' => $cta['text'], 'primaryLabel' => $view->t('site.cta.book'), 'primaryHref' => $cta['bookHref'], 'secondaryLabel' => $cta['phoneHref'] !== null ? $view->t('site.cta.call') : null, 'secondaryHref' => $cta['phoneHref']]) ?>

</section>
