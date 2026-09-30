<?php
/**
 * Call-to-action band.
 *
 * @var \Gate\Core\View $view
 * @var array{title: string, text: string, primary: array{label: string, href: string}|null, secondary: array{label: string, href: string}|null} $cta
 */
?>
<section class="cta">
  <div class="wrap">
    <div class="cta__box reveal">
      <div class="cta__text">
        <h2><?= e($cta['title']) ?></h2>
        <p><?= e($cta['text']) ?></p>
      </div>
      <div class="cta__actions">
<?php if ($cta['primary'] !== null): ?>
        <a class="btn btn--white" href="<?= e_url($cta['primary']['href']) ?>"><?= e($cta['primary']['label']) ?></a>
<?php endif; ?>
<?php if ($cta['secondary'] !== null): ?>
        <a class="btn btn--ghost" href="<?= e_url($cta['secondary']['href']) ?>"><?= e($cta['secondary']['label']) ?></a>
<?php endif; ?>
      </div>
    </div>
  </div>
</section>
