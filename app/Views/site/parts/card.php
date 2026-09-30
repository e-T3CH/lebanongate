<?php
/**
 * Entry card (project, article, publication, album) for lists and home sections.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, type: string, title: string, summary: string, href: string, cover: array{url: string, alt: string, width: int, height: int}|null, date: string, badge: array{0: string, 1: string}, tag: string, status: string, statusLabel: string, region: string, meta: list<array{icon: string, text: string}>} $card
 * @var string $heading h2|h3
 */
$heading = in_array($heading ?? 'h3', ['h2', 'h3'], true) ? ($heading ?? 'h3') : 'h3';
$showDate = in_array($card['type'], ['news', 'album'], true);
?>
<article class="card card--<?= e_attr($card['type']) ?> reveal">
  <a class="card__link" href="<?= e_url($card['href']) ?>" aria-label="<?= e_attr($card['title']) ?>"></a>
  <div class="card__media">
<?php if ($card['cover'] !== null): ?>
    <img src="<?= e_url($card['cover']['url']) ?>" alt="<?= e_attr($card['cover']['alt']) ?>" width="<?= (int) $card['cover']['width'] ?>" height="<?= (int) $card['cover']['height'] ?>" loading="lazy" decoding="async">
<?php else: ?>
    <div class="ph" aria-hidden="true"><?= svg_icon($card['type'] === 'publication' ? 'file' : ($card['type'] === 'album' ? 'image' : 'cedar'), 'ph__icon') ?></div>
<?php endif; ?>
<?php if ($card['tag'] !== ''): ?>
    <span class="chip chip--float"><?= e($card['tag']) ?></span>
<?php endif; ?>
<?php if ($showDate && $card['badge'][0] !== ''): ?>
    <span class="date-badge" aria-hidden="true"><b><?= e($card['badge'][0]) ?></b><small><?= e($card['badge'][1]) ?></small></span>
<?php endif; ?>
  </div>
  <div class="card__body">
<?php if ($card['statusLabel'] !== ''): ?>
    <span class="chip chip--status chip--<?= e_attr($card['status']) ?>"><?= e($card['statusLabel']) ?></span>
<?php endif; ?>
    <<?= $heading ?> class="card__title"><?= e($card['title']) ?></<?= $heading ?>>
<?php if ($showDate && $card['date'] !== ''): ?>
    <p class="card__date"><?= svg_icon('calendar') ?><time><?= e($card['date']) ?></time></p>
<?php endif; ?>
<?php if ($card['summary'] !== '' && $card['type'] !== 'album'): ?>
    <p class="card__text"><?= e($card['summary']) ?></p>
<?php endif; ?>
<?php if ($card['meta'] !== []): ?>
    <ul class="card__meta">
<?php foreach ($card['meta'] as $meta): ?>
      <li><?= svg_icon($meta['icon']) ?><span><?= e($meta['text']) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php else: ?>
    <span class="link-more" aria-hidden="true"><?= e($view->t('site.read_more')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></span>
<?php endif; ?>
  </div>
</article>
