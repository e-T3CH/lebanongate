<?php
/**
 * About us: banner, the text, the three values, the child pages (who we are, mission & vision, profile) as cards,
 * the impact counters and the call to action.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var string $body
 * @var list<array{title: string, intro: string, href: string}> $children
 * @var list<array{id: int, value: string, label: string}> $stats
 * @var list<mixed> $values
 * @var array<string, mixed> $cta
 */
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section">
  <div class="wrap about-page">
    <div class="prose reveal"><?= $body /* RichText::render(): sanitised */ ?></div>
<?php if ($values !== []): ?>
    <ul class="values values--card" data-stagger>
<?php foreach ($values as $point): ?>
<?php if (!is_array($point)) { continue; } ?>
      <li class="reveal"><span class="values__icon"><?= svg_icon(is_string($point['icon'] ?? null) ? $point['icon'] : 'check') ?></span><div><h2 class="h4"><?= e((string) ($point['title'] ?? '')) ?></h2><p><?= e((string) ($point['text'] ?? '')) ?></p></div></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
</section>
<?php if ($children !== []): ?>
<section class="section section--alt">
  <div class="wrap">
    <div class="grid grid--3" data-stagger>
<?php foreach ($children as $child): ?>
      <article class="xp-card reveal">
        <span class="xp-card__icon"><?= svg_icon('cedar') ?></span>
        <h2 class="xp-card__title"><a href="<?= e_url($child['href']) ?>"><?= e($child['title']) ?></a></h2>
<?php if ($child['intro'] !== ''): ?>
        <p><?= e($child['intro']) ?></p>
<?php endif; ?>
        <span class="link-more" aria-hidden="true"><?= e($view->t('site.read_more')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></span>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($stats !== []): ?>
<?= $view->render('site/sections/stats', ['s' => ['label' => '', 'stats' => $stats]]) ?>
<?php endif; ?>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
