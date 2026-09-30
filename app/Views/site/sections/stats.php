<?php
/**
 * Home section "stats": key figures band.
 *
 * @var \Gate\Core\View $view
 * @var array{label: string} $section
 * @var array<string, mixed> $home
 */
/** @var list<array{value: string, label: string}> $stats */
$stats = $home['stats'];
?>
<?php if ($stats !== []): ?>
<?= $view->component('stats', ['items' => $stats, 'label' => $section['label']]) ?>

<?php endif; ?>
