<?php
/**
 * Error page (404, 403, 419, 503 maintenance, 500) inside the site layout.
 *
 * @var \Gate\Core\View $view
 * @var int $status
 * @var string $key
 * @var string $homeHref
 * @var string|null $contactHref
 * @var string|null $reference
 */
?>
<section class="section error-page">
  <div class="wrap narrow error-page__inner">
    <p class="error-page__code" aria-hidden="true"><?= svg_icon('cedar', 'error-page__cedar') ?><span><?= (int) $status ?></span></p>
    <h1 class="h2"><?= e($view->t('site.errors.' . $key . '_title')) ?></h1>
    <p class="lead"><?= e($view->t('site.errors.' . $key . '_text')) ?></p>
<?php if ($reference !== null): ?>
    <p class="muted"><?= e($view->t('site.errors.reference', ['ref' => $reference])) ?></p>
<?php endif; ?>
    <div class="error-page__actions">
      <a class="btn btn--primary" href="<?= e_url($homeHref) ?>"><?= e($view->t('site.errors.home')) ?></a>
<?php if ($contactHref !== null): ?>
      <a class="btn btn--outline" href="<?= e_url($contactHref) ?>"><?= e($view->t('site.errors.contact')) ?></a>
<?php endif; ?>
    </div>
  </div>
</section>
