<?php
/**
 * Social links: icon buttons for the networks that have a URL (the caller passes only enabled links).
 * Parameters: Components::SPECS['social-links'].
 *
 * @var \Gate\Core\View $view
 * @var array{links: list<array{network: string, url: string}>, class: string} $p
 */

use Gate\Core\Components;
use Gate\Core\Props;

$items = '';
foreach ($p['links'] as $link) {
    $network = is_array($link) && is_string($link['network'] ?? null) ? $link['network'] : '';
    if (!isset(Components::SOCIAL_ICONS[$network]) || !is_string($link['url'] ?? null)) {
        throw new \InvalidArgumentException('social-links: invalid link ' . $network);
    }
    $items .= '<a class="soc" href="' . e_url($link['url']) . '" aria-label="' . e_attr($view->t('ui.social.' . $network)) . '">'
        . $view->component('icon', ['icon' => Components::SOCIAL_ICONS[$network], 'size' => '17']) . '</a>';
}
?>
<?php if ($items !== ''): ?>
<div class="<?= e_attr(Props::classes('socials', $p['class'])) ?>"><?= $items ?></div>
<?php endif; ?>
