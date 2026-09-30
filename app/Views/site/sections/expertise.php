<?php
/**
 * Home: areas of expertise as cards (icon, title, summary, link).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, title, intro, areas
 */
?>
<section class="section section--alt" id="expertise">
  <div class="wrap">
    <div class="section-head section-head--center" data-stagger>
<?php if ($s['label'] !== ''): ?>
      <p class="eyebrow reveal"><?= e($s['label']) ?></p>
<?php endif; ?>
      <h2 class="h2 reveal"><?= e($s['title']) ?></h2>
<?php if ($s['intro'] !== ''): ?>
      <p class="reveal"><?= e($s['intro']) ?></p>
<?php endif; ?>
    </div>
    <?= $view->render('site/parts/expertise-grid', ['areas' => $s['areas'], 'heading' => 'h3']) ?>
  </div>
</section>
