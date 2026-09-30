<?php
/**
 * About: heading, rich text, key figures, process steps, partners (when any are enabled), call to action.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var array<string, mixed> $home
 * @var array<string, mixed> $process
 * @var list<array{name: string, url: string, logo: string, description: string}> $partners
 * @var array{title: string, text: string, bookHref: string, phoneHref: ?string} $cta
 */
/** @var list<array{value: string, label: string}> $stats */
$stats = $home['stats'];
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => (string) $page['title'], 'highlight' => (string) $page['highlight'], 'lead' => (string) $page['intro'], 'breadcrumbs' => $page['breadcrumbs']]) ?>

<section class="sec" aria-label="<?= e_attr((string) $page['nav_label']) ?>">
  <?= $view->component('prose', ['html' => (string) $page['body']]) ?>

</section>
<?php if ($stats !== []): ?>
<?= $view->component('stats', ['items' => $stats, 'label' => (string) $home['statsLabel']]) ?>

<?php endif; ?>
<?= $view->render('site/sections/process', ['section' => $process, 'home' => $home]) ?>
<?php if ($partners !== []): ?>
<section class="sec">
  <div class="sec__head"><h2><?= e($view->t('site.partners.title')) ?></h2></div>
  <div class="partners">
<?php foreach ($partners as $partner): ?>
    <div class="card partner">
<?php if ($partner['logo'] !== ''): ?>
      <?= $view->component('picture', ['src' => $partner['logo'], 'alt' => $partner['name'], 'width' => 40, 'height' => 40, 'widths' => [40, 80], 'sizes' => '40px']) ?>

<?php endif; ?>
      <?= $partner['url'] !== '' ? '<a href="' . e_url($partner['url']) . '" rel="noopener">' . e($partner['name']) . '</a>' : '<strong>' . e($partner['name']) . '</strong>' ?>

<?php if ($partner['description'] !== ''): ?>
      <p class="type__text"><?= e($partner['description']) ?></p>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<section class="sec sec--last">
  <?= $view->component('cta-band', ['title' => $cta['title'], 'text' => $cta['text'], 'primaryLabel' => $view->t('site.cta.book'), 'primaryHref' => $cta['bookHref'], 'secondaryLabel' => $cta['phoneHref'] !== null ? $view->t('site.cta.call') : null, 'secondaryHref' => $cta['phoneHref']]) ?>

</section>
