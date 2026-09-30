<?php
/**
 * Process step: numbered dot with connector line (desktop row, vertical timeline below 768px), title, text.
 * Parameters: Components::SPECS['process-step'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{number: string, title: string, text: string} $p
 */
?>
<div class="step">
  <div class="step__top"><span class="step__dot m-step-dot"><?= e($p['number']) ?></span><span class="step__line m-connector"></span></div>
  <div class="step__body"><div class="step__title"><?= e($p['title']) ?></div><div class="step__text"><?= e($p['text']) ?></div></div>
</div>
