<?php
/**
 * Key figures band: numbered stats (value counts up on scroll through motion.js).
 * Parameters: Components::SPECS['stats'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{items: list<array{value: string, label: string}>, label: string} $p
 */
?>
<section class="stats" aria-label="<?= e_attr($p['label']) ?>">
<?php foreach ($p['items'] as $i => $item): ?>
<?php
if (!is_array($item) || !is_string($item['value'] ?? null) || !is_string($item['label'] ?? null)) {
    throw new \InvalidArgumentException('stats: value and label are required');
}
?>
  <div class="stat"><span class="stat__num"><?= sprintf('%02d', $i + 1) ?></span><span class="stat__value"><?= e($item['value']) ?></span><span class="stat__label"><?= e($item['label']) ?></span></div>
<?php endforeach; ?>
</section>
