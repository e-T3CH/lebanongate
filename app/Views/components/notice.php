<?php
/**
 * Notice card (admin): warning-tinted card with icon tile, title, text and an optional link.
 * role=alert for errors after a submit, note for static hints.
 * Parameters: Components::SPECS['notice'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{title: string, text: ?string, icon: string, linkHref: ?string, linkLabel: ?string, role: string, class: string} $p
 */

use BMMatic\Core\Props;

$col = '<span class="warn__title">' . e($p['title']) . '</span>'
    . ($p['text'] !== null ? '<span class="warn__text">' . e($p['text']) . '</span>' : '')
    . ($p['linkHref'] !== null && $p['linkLabel'] !== null ? '<a class="warn__link" href="' . e_url($p['linkHref']) . '">' . e($p['linkLabel']) . '</a>' : '');
?>
<div class="<?= e_attr(Props::classes('acard warn', $p['class'])) ?>" role="<?= e_attr($p['role']) ?>">
  <span class="tile"><?= $view->component('icon', ['icon' => $p['icon'], 'size' => '18']) ?></span>
  <span class="warn__col"><?= $col ?></span>
</div>
