<?php
/**
 * Home: featured projects (featured first, then newest).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: label, title, intro, cards, href
 */
?>
<section class="section" id="projects">
  <div class="wrap">
    <div class="head-row" data-stagger>
      <div class="section-head">
<?php if ($s['label'] !== ''): ?>
        <p class="eyebrow reveal"><?= e($s['label']) ?></p>
<?php endif; ?>
        <h2 class="h2 reveal"><?= e($s['title']) ?></h2>
      </div>
<?php if (is_string($s['href'])): ?>
      <a class="btn btn--outline reveal" href="<?= e_url($s['href']) ?>"><?= e($view->t('site.projects.all')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a>
<?php endif; ?>
    </div>
    <div class="grid grid--3" data-stagger>
<?php foreach ($s['cards'] as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h3']) ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
