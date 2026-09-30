<?php
/**
 * Home section "services": header with the "All services" link and the service cards.
 *
 * @var \Gate\Core\View $view
 * @var array{number: ?string, label: string, title: string, highlight: string, extra: array<string, mixed>} $section
 * @var array<string, mixed> $home
 */
/** @var list<array{number: string, icon: string, title: string, text: string, href: string}> $services */
$services = $home['services'];
$link = is_string($section['extra']['link'] ?? null) ? $section['extra']['link'] : '';
?>
<section class="sec sec--services" id="services">
  <div class="sec__row">
    <?= $view->component('section-header', ['number' => $section['number'], 'label' => $section['label'], 'title' => $section['title'], 'highlight' => $section['highlight'] !== '' ? $section['highlight'] : null]) ?>

<?php if ($link !== '' && is_string($home['servicesHref'] ?? null)): ?>
    <a class="sec__link" href="<?= e_url($home['servicesHref']) ?>"><?= e($link) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'class' => 'ic-ne']) ?></a>
<?php endif; ?>
  </div>
  <div class="svc-grid">
<?php foreach ($services as $s): ?>
    <?= $view->component('service-card', ['number' => $s['number'], 'icon' => $s['icon'], 'title' => $s['title'], 'text' => $s['text'], 'href' => $s['href']]) ?>

<?php endforeach; ?>
  </div>
</section>
