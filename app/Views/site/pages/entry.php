<?php
/**
 * One project, article, publication or album: banner, cover, text, photo gallery (lightbox), key facts, download,
 * related project, updates of a project and more entries of the same kind.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page the list page
 * @var array<string, mixed> $entry
 * @var string $type
 * @var string $body
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array{title: string, href: string}|null $area
 * @var list<array{id: int, url: string, alt: string, width: int, height: int}> $gallery
 * @var array{href: string, size: string, pages: int}|null $file
 * @var array{title: string, href: string}|null $related
 * @var list<array{label: string, value: string, href?: string}> $facts
 * @var string $date
 * @var list<array<string, mixed>> $more
 * @var list<array<string, mixed>> $updates
 * @var string $backHref
 * @var array<string, mixed> $cta
 */
$cover = $entry['cover'];
$label = $area !== null ? $area['title'] : ($page['nav_label'] ?? '');
?>
<?= $view->render('site/parts/page-hero', ['title' => $entry['title'], 'intro' => $entry['summary'], 'label' => $label, 'breadcrumbs' => $breadcrumbs, 'image' => null]) ?>
<section class="section section--entry">
  <div class="wrap<?= $facts !== [] || $file !== null || $related !== null ? ' with-aside' : ' narrow' ?>">
    <article class="entry">
<?php if (in_array($type, ['news', 'album'], true) && $date !== ''): ?>
      <p class="entry__date"><?= svg_icon('calendar') ?><time datetime="<?= e_attr($entry['published_on']) ?>"><?= e($date) ?></time></p>
<?php endif; ?>
<?php if ($cover !== null && $type !== 'album'): ?>
      <figure class="entry__cover reveal">
        <img src="<?= e_url($cover['url']) ?>" alt="<?= e_attr($cover['alt']) ?>" width="<?= (int) $cover['width'] ?>" height="<?= (int) $cover['height'] ?>" fetchpriority="high">
      </figure>
<?php endif; ?>
<?php if (trim(strip_tags($body)) !== ''): ?>
      <div class="prose reveal"><?= $body /* RichText::render(): sanitised */ ?></div>
<?php endif; ?>
<?php if ($gallery !== []): ?>
      <div class="gallery" data-gallery>
<?php if ($type !== 'album'): ?>
        <h2 class="h3"><?= e($view->t('site.entries.gallery')) ?></h2>
<?php endif; ?>
        <ul class="gallery__grid" data-stagger>
<?php foreach ($gallery as $i => $photo): ?>
          <li class="reveal"><a class="gallery__item" href="<?= e_url($photo['url']) ?>" data-lightbox="<?= (int) $i ?>" data-caption="<?= e_attr($photo['alt']) ?>"><img src="<?= e_url($photo['url']) ?>" alt="<?= e_attr($photo['alt']) ?>" width="<?= (int) $photo['width'] ?>" height="<?= (int) $photo['height'] ?>" loading="lazy" decoding="async"></a></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>
      <p class="entry__back"><a class="link-more link-more--back" href="<?= e_url($backHref) ?>"><?= svg_icon('arrow', 'ic ic--arrow ic--back') ?> <?= e($view->t('site.entries.back', ['list' => $page['nav_label']])) ?></a></p>
    </article>
<?php if ($facts !== [] || $file !== null || $related !== null): ?>
    <aside class="entry-aside">
<?php if ($file !== null): ?>
      <div class="aside-card aside-card--download reveal">
        <?= svg_icon('file', 'ic aside-card__icon') ?>
        <p class="aside-card__title"><?= e($view->t('site.entries.download_title')) ?></p>
        <p class="aside-card__meta">PDF · <?= e($file['size']) ?><?= $file['pages'] > 0 ? ' · ' . e($view->t('site.entries.pages_n', ['n' => $file['pages']])) : '' ?></p>
        <a class="btn btn--primary btn--block" href="<?= e_url($file['href']) ?>" target="_blank" rel="noopener"><?= svg_icon('download') ?> <?= e($view->t('site.entries.download')) ?></a>
      </div>
<?php endif; ?>
<?php if ($facts !== []): ?>
      <div class="aside-card reveal">
        <p class="aside-card__title"><?= e($view->t('site.entries.facts')) ?></p>
        <dl class="facts">
<?php foreach ($facts as $fact): ?>
          <div><dt><?= e($fact['label']) ?></dt><dd><?php if (isset($fact['href'])): ?><a href="<?= e_url($fact['href']) ?>"><?= e($fact['value']) ?></a><?php else: ?><?= e($fact['value']) ?><?php endif; ?></dd></div>
<?php endforeach; ?>
        </dl>
      </div>
<?php endif; ?>
<?php if ($related !== null): ?>
      <div class="aside-card reveal">
        <p class="aside-card__title"><?= e($view->t('site.entries.related')) ?></p>
        <a class="link-more" href="<?= e_url($related['href']) ?>"><?= e($related['title']) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></a>
      </div>
<?php endif; ?>
    </aside>
<?php endif; ?>
  </div>
</section>
<?php if ($updates !== []): ?>
<section class="section section--alt">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($view->t('site.entries.updates')) ?></h2>
    <div class="grid grid--3" data-stagger>
<?php foreach ($updates as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h3']) ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($more !== []): ?>
<section class="section<?= $updates === [] ? ' section--alt' : '' ?>">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($view->t('site.entries.more_' . $type)) ?></h2>
    <div class="grid grid--3" data-stagger>
<?php foreach ($more as $card): ?>
      <?= $view->render('site/parts/card', ['card' => $card, 'heading' => 'h3']) ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($gallery !== []): ?>
<dialog class="lightbox" data-lightbox-dialog aria-label="<?= e_attr($view->t('site.lightbox.label')) ?>">
  <figure class="lightbox__figure"><img alt="" data-lightbox-img><figcaption data-lightbox-caption></figcaption></figure>
  <button type="button" class="lightbox__btn lightbox__close" data-lightbox-close aria-label="<?= e_attr($view->t('site.close')) ?>"><?= svg_icon('close') ?></button>
  <button type="button" class="lightbox__btn lightbox__prev" data-lightbox-prev aria-label="<?= e_attr($view->t('site.lightbox.previous')) ?>"><?= svg_icon('chevron', 'ic ic--prev') ?></button>
  <button type="button" class="lightbox__btn lightbox__next" data-lightbox-next aria-label="<?= e_attr($view->t('site.lightbox.next')) ?>"><?= svg_icon('chevron') ?></button>
  <p class="lightbox__count" data-lightbox-count aria-live="polite"></p>
</dialog>
<?php endif; ?>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
