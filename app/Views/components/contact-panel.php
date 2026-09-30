<?php
/**
 * Contact panel (as drawn in the home contact section): label, heading, intro, contact lines with icons, map tile.
 * Parameters: Components::SPECS['contact-panel'].
 *
 * @var \Gate\Core\View $view
 * @var array{title: string, label: ?string, number: ?string, intro: ?string, items: list<array{icon: string, text: string, href?: ?string}>, mapLabel: string, mapHref: ?string, headingLevel: string} $p
 */

use Gate\Core\Props;

$lines = '';
foreach ($p['items'] as $item) {
    if (!is_array($item) || !is_string($item['icon'] ?? null) || !Props::isIcon($item['icon']) || !is_string($item['text'] ?? null)) {
        throw new \InvalidArgumentException('contact-panel: icon and text are required');
    }
    $text = is_string($item['href'] ?? null) ? '<a href="' . e_url($item['href']) . '">' . e($item['text']) . '</a>' : e($item['text']);
    $lines .= "\n      " . '<span class="contact-list__item">' . $view->component('icon', ['icon' => $item['icon'], 'size' => '18', 'class' => 'ic-accent']) . ' ' . $text . '</span>';
}
$mapIcon = $view->component('icon', ['icon' => 'fa-solid fa-location-dot', 'size' => '34', 'class' => 'ic-accent']);
?>
<div class="panel">
    <?= $p['label'] !== null ? $view->component('section-label', ['text' => $p['label'], 'number' => $p['number']]) : '' ?>

    <<?= $p['headingLevel'] ?>><?= e($p['title']) ?></<?= $p['headingLevel'] ?>>
<?php if ($p['intro'] !== null && $p['intro'] !== ''): ?>
    <p class="panel__lead"><?= e($p['intro']) ?></p>
<?php endif; ?>
    <div class="contact-list"><?= $lines ?>

    </div>
    <div class="map">
<?php if ($p['mapHref'] !== null): ?>
      <a class="map__label" href="<?= e_url($p['mapHref']) ?>" rel="noopener" target="_blank"><?= $mapIcon ?> <?= e($p['mapLabel']) ?></a>
<?php else: ?>
      <span class="map__label"><?= $mapIcon ?> <?= e($p['mapLabel']) ?></span>
<?php endif; ?>
    </div>
  </div>
