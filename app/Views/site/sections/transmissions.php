<?php
/**
 * Home section "transmissions": caption and transmission chips (as drawn on desktop; not part of the mobile layout).
 *
 * @var \BMMatic\Core\View $view
 * @var array{label: string} $section
 * @var array<string, mixed> $home
 */
/** @var list<string> $types */
$types = $home['types'];
?>
<?php if ($types !== []): ?>
<section class="sec sec--types">
  <div class="cap"><?= e($section['label']) ?></div>
  <?= $view->component('chips', ['items' => $types]) ?>

</section>
<?php endif; ?>
