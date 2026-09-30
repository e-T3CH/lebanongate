<?php
/**
 * Call-to-action band: title, short text, primary button and optional secondary (ghost) button.
 * Parameters: Components::SPECS['cta-band'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{title: string, text: ?string, primaryLabel: string, primaryHref: string, secondaryLabel: ?string, secondaryHref: ?string} $p
 */
?>
<div class="cta">
  <div class="cta__text"><h2 class="cta__title"><?= e($p['title']) ?></h2><?php if ($p['text'] !== null): ?><p class="cta__lead"><?= e($p['text']) ?></p><?php endif; ?></div>
  <div class="cta__actions"><?= $view->component('button', ['label' => $p['primaryLabel'], 'href' => $p['primaryHref'], 'icon' => 'fa-solid fa-arrow-right']) ?><?php if ($p['secondaryLabel'] !== null && $p['secondaryHref'] !== null): ?><?= $view->component('button', ['label' => $p['secondaryLabel'], 'href' => $p['secondaryHref'], 'variant' => 'ghost', 'icon' => 'fa-solid fa-phone', 'iconPosition' => 'start', 'iconClass' => 'ic-accent']) ?><?php endif; ?></div>
</div>
