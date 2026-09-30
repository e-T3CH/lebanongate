<?php
/**
 * Home: "Where we work" — the governorates of Lebanon; the ones with projects are coloured and link to the project
 * list filtered by region. The list beside the map says the same in text (the map itself is decorative for screen
 * readers). Region notes come from the section's extra texts.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, title, intro, regions (region => projects), extra{notes: region => text, main: region}, regionHref, projectsHref
 */
$map = require __DIR__ . '/../parts/map-paths.php';
/** @var array<string, int> $counts */
$counts = $s['regions'];
$notes = is_array($s['extra']['notes'] ?? null) ? $s['extra']['notes'] : [];
$main = is_string($s['extra']['main'] ?? null) ? $s['extra']['main'] : '';
$listed = array_values(array_unique(array_merge($main !== '' ? [$main] : [], array_keys($counts), array_keys($notes))));
?>
<section class="section section--alt" id="where-we-work">
  <div class="wrap where">
    <div data-stagger>
<?php if ($s['label'] !== ''): ?>
      <p class="eyebrow reveal"><?= e($s['label']) ?></p>
<?php endif; ?>
      <h2 class="h2 reveal"><?= e($s['title']) ?></h2>
<?php if ($s['intro'] !== ''): ?>
      <p class="lead reveal"><?= e($s['intro']) ?></p>
<?php endif; ?>
      <ul class="regions">
<?php foreach ($listed as $region): ?>
<?php if (!isset($map['regions'][$region]) && $region !== 'national') { continue; } ?>
        <li class="regions__item reveal<?= $region === $main ? ' is-main' : '' ?>">
<?php if (($counts[$region] ?? 0) > 0): ?>
          <a href="<?= e_url(($s['regionHref'])($region)) ?>"><b><?= e($view->t('site.regions.' . $region)) ?></b><span><?= e(is_string($notes[$region] ?? null) ? $notes[$region] : $view->t('site.map.projects', ['n' => $counts[$region]])) ?></span></a>
<?php else: ?>
          <div><b><?= e($view->t('site.regions.' . $region)) ?></b><span><?= e(is_string($notes[$region] ?? null) ? $notes[$region] : '') ?></span></div>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
    </div>
    <figure class="map-card reveal reveal--end">
      <svg class="map" viewBox="<?= e_attr($map['viewBox']) ?>" role="img" aria-labelledby="map-title">
        <title id="map-title"><?= e($view->t('site.map.title')) ?></title>
<?php foreach ($map['regions'] as $region => $shape): ?>
<?php $active = ($counts[$region] ?? 0) > 0 || isset($notes[$region]); ?>
        <path class="map__region<?= $active ? ' is-active' : '' ?><?= $region === $main ? ' is-main' : '' ?>" d="<?= e_attr($shape['d']) ?>"><title><?= e($view->t('site.regions.' . $region)) ?></title></path>
<?php endforeach; ?>
<?php if ($main !== '' && isset($map['regions'][$main])): ?>
        <g class="map__pin" transform="translate(<?= e_attr((string) $map['regions'][$main]['x']) ?> <?= e_attr((string) $map['regions'][$main]['y']) ?>)"><circle r="14" class="map__pulse"/><circle r="6"/></g>
<?php endif; ?>
      </svg>
      <figcaption class="map-card__note"><?= e($view->t('site.map.credit')) ?></figcaption>
    </figure>
  </div>
</section>
