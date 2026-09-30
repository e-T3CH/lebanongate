<?php
/**
 * Mobile quick-action dock (fixed at the bottom below 768px): call, WhatsApp, book (primary).
 * Parameters: Components::SPECS['mobile-dock'].
 *
 * @var \Gate\Core\View $view
 * @var array{items: list<array{label: string, icon: string, href: string, primary?: bool}>, label: string} $p
 */

use Gate\Core\Props;

?>
<nav class="dock m-dock<?= count($p['items']) === 2 ? ' dock--2' : '' ?>" aria-label="<?= e_attr($p['label']) ?>">
<?php foreach ($p['items'] as $item): ?>
<?php
if (!is_array($item) || !is_string($item['label'] ?? null) || !is_string($item['icon'] ?? null) || !Props::isIcon($item['icon']) || !is_string($item['href'] ?? null)) {
    throw new \InvalidArgumentException('mobile-dock: label, icon and href are required');
}
$primary = ($item['primary'] ?? false) === true;
?>
  <a<?= $primary ? ' class="dock__book"' : '' ?> href="<?= e_url($item['href']) ?>"><?= $view->component('icon', ['icon' => $item['icon'], 'size' => '18', 'class' => $primary ? '' : 'ic-accent']) ?> <?= e($item['label']) ?></a>
<?php endforeach; ?>
</nav>
