<?php
/**
 * Services overview: page heading, all service cards, the process steps and a call to action.
 *
 * @var \BMMatic\Core\View $view
 * @var array<string, mixed> $page
 * @var array<string, mixed> $home
 * @var array<string, mixed> $process process section texts
 * @var array{title: string, text: string, bookHref: string, phoneHref: ?string} $cta
 */
/** @var list<array{number: string, icon: string, title: string, text: string, href: string}> $services */
$services = $home['services'];
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => (string) $page['title'], 'highlight' => (string) $page['highlight'], 'lead' => (string) $page['intro'], 'breadcrumbs' => $page['breadcrumbs']]) ?>

<section class="sec sec--services" aria-label="<?= e_attr((string) $page['nav_label']) ?>">
  <div class="svc-grid">
<?php foreach ($services as $s): ?>
    <?= $view->component('service-card', ['number' => $s['number'], 'icon' => $s['icon'], 'title' => $s['title'], 'text' => $s['text'], 'href' => $s['href']]) ?>

<?php endforeach; ?>
  </div>
</section>
<?= $view->render('site/sections/process', ['section' => $process, 'home' => $home]) ?>
<section class="sec sec--last">
  <?= $view->component('cta-band', ['title' => $cta['title'], 'text' => $cta['text'], 'primaryLabel' => $view->t('site.cta.book'), 'primaryHref' => $cta['bookHref'], 'secondaryLabel' => $cta['phoneHref'] !== null ? $view->t('site.cta.call') : null, 'secondaryHref' => $cta['phoneHref']]) ?>

</section>
