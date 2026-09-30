<?php
/**
 * A short message page (newsletter confirmation and unsubscribe links).
 *
 * @var \Gate\Core\View $view
 * @var string $title
 * @var string $text
 * @var string $homeHref
 * @var bool $ok
 */
?>
<section class="section error-page">
  <div class="wrap narrow error-page__inner">
    <p class="notice-icon<?= $ok ? ' notice-icon--ok' : '' ?>" aria-hidden="true"><?= svg_icon($ok ? 'check' : 'info') ?></p>
    <h1 class="h2"><?= e($title) ?></h1>
    <p class="lead"><?= e($text) ?></p>
    <div class="error-page__actions"><a class="btn btn--primary" href="<?= e_url($homeHref) ?>"><?= e($view->t('site.errors.home')) ?></a></div>
  </div>
</section>
