<?php
/**
 * Service detail: heading, description with an aside to book or call, other services and a call to action.
 *
 * @var \BMMatic\Core\View $view
 * @var array<string, mixed> $page services page
 * @var array{title: string, summary: string, body: string} $service
 * @var list<array{label: string, href?: ?string}> $breadcrumbs
 * @var list<array{number: string, icon: string, title: string, text: string, href: string}> $others
 * @var array<string, mixed> $home
 * @var array{title: string, text: string, bookHref: string, phoneHref: ?string} $cta
 */
/** @var array{items: list<array{icon: string, text: string, href?: ?string}>} $contact */
$contact = $home['contact'];
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => $service['title'], 'lead' => $service['summary'], 'breadcrumbs' => $breadcrumbs]) ?>

<section class="sec" aria-label="<?= e_attr($service['title']) ?>">
  <div class="detail">
    <?= $view->component('prose', ['html' => $service['body']]) ?>

    <aside class="card aside">
      <h2 class="aside__title"><?= e($view->t('site.service.aside_title')) ?></h2>
      <p class="aside__text"><?= e($view->t('site.service.aside_text')) ?></p>
      <?= $view->component('button', ['label' => $view->t('site.cta.book'), 'href' => $cta['bookHref'], 'icon' => 'fa-solid fa-arrow-right']) ?>

<?php if ($cta['phoneHref'] !== null): ?>
      <?= $view->component('button', ['label' => $view->t('site.service.call'), 'variant' => 'ghost', 'href' => $cta['phoneHref'], 'icon' => 'fa-solid fa-phone', 'iconPosition' => 'start', 'iconClass' => 'ic-accent']) ?>

<?php endif; ?>
<?php foreach (array_slice($contact['items'], 1) as $item): ?>
      <span class="contact-list__item"><?= $view->component('icon', ['icon' => $item['icon'], 'size' => '18', 'class' => 'ic-accent']) ?> <?= is_string($item['href'] ?? null) ? '<a href="' . e_url($item['href']) . '">' . e($item['text']) . '</a>' : e($item['text']) ?></span>
<?php endforeach; ?>
    </aside>
  </div>
</section>
<?php if ($others !== []): ?>
<section class="sec sec--services">
  <div class="sec__head"><h2><?= e($view->t('site.service.other')) ?></h2></div>
  <div class="svc-grid">
<?php foreach ($others as $s): ?>
    <?= $view->component('service-card', ['number' => $s['number'], 'icon' => $s['icon'], 'title' => $s['title'], 'text' => $s['text'], 'href' => $s['href']]) ?>

<?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<section class="sec sec--last">
  <?= $view->component('cta-band', ['title' => $cta['title'], 'text' => $cta['text'], 'primaryLabel' => $view->t('site.cta.book'), 'primaryHref' => $cta['bookHref'], 'secondaryLabel' => $cta['phoneHref'] !== null ? $view->t('site.cta.call') : null, 'secondaryHref' => $cta['phoneHref']]) ?>

</section>
