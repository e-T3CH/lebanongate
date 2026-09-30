<?php
/**
 * One area of expertise: text, related projects and news, the other areas.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $area
 * @var array<string, mixed>|null $cover
 * @var string $body
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var list<array<string, mixed>> $projects
 * @var string|null $projectsHref
 * @var list<array<string, mixed>> $news
 * @var list<array<string, mixed>> $others
 * @var array<string, mixed> $cta
 */
?>
<?= $view->render('site/parts/page-hero', ['title' => $area['title'], 'intro' => $area['summary'], 'label' => $view->t('site.expertise.label'), 'breadcrumbs' => $breadcrumbs, 'image' => $cover]) ?>
<section class="section">
  <div class="wrap narrow">
    <div class="area-icon reveal"><?= svg_icon($area['icon']) ?></div>
    <div class="prose reveal"><?= $body /* RichText::render(): sanitised */ ?></div>
  </div>
</section>
<?php if ($projects !== []): ?>
<section class="section section--alt">
  <div class="wrap">
    <div class="head-row">
      <h2 class="h2 reveal"><?= e($view->t('site.expertise.projects', ['area' => $area['title']])) ?></h2>
<?php if ($projectsHref !== null): ?>
      <a class="btn btn--outline reveal" href="<?= e_url($projectsHref) ?>"><?= e($view->t('site.projects.all')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a>
<?php endif; ?>
    </div>
    <div class="grid grid--3" data-stagger>
<?php foreach ($projects as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h3']) ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($news !== []): ?>
<section class="section">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($view->t('site.expertise.news')) ?></h2>
    <div class="grid grid--3" data-stagger>
<?php foreach ($news as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h3']) ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($others !== []): ?>
<section class="section section--alt">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($view->t('site.expertise.others')) ?></h2>
    <?= $view->render('site/parts/expertise-grid', ['areas' => $others, 'heading' => 'h3']) ?>
  </div>
</section>
<?php endif; ?>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
