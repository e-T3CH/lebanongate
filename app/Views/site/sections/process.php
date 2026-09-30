<?php
/**
 * Home section "process": header and the numbered steps.
 *
 * @var \Gate\Core\View $view
 * @var array{number: ?string, label: string, title: string, highlight: string} $section
 * @var array<string, mixed> $home
 */
/** @var list<array{number: string, title: string, text: string}> $steps */
$steps = $home['steps'];
?>
<section class="sec sec--process">
  <?= $view->component('section-header', ['number' => $section['number'], 'label' => $section['label'], 'title' => $section['title'], 'highlight' => $section['highlight'] !== '' ? $section['highlight'] : null]) ?>

  <div class="steps">
<?php foreach ($steps as $step): ?>
    <?= $view->component('process-step', $step) ?>

<?php endforeach; ?>
  </div>
</section>
