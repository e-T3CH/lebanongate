<?php
/**
 * Cookie banner: shown while no choice was made (only when an analytics provider is set up) or when the visitor opened
 * "Cookie settings". Works without JavaScript: two submit buttons post the choice.
 *
 * @var \Gate\Core\View $view
 * @var array{action: string, returnTo: string, policyHref: string|null} $cookie
 */
?>
<section class="cookie" id="cookie-consent" aria-labelledby="cookie-title" data-cookie>
  <form method="post" action="<?= e_url($cookie['action']) ?>">
    <?= $view->csrfField() ?>
    <input type="hidden" name="return" value="<?= e_attr($cookie['returnTo']) ?>">
    <h2 class="cookie__title" id="cookie-title"><?= e($view->t('site.consent.title')) ?></h2>
    <p><?= e($view->t('site.consent.text')) ?><?php if ($cookie['policyHref'] !== null): ?> <a href="<?= e_url($cookie['policyHref']) ?>"><?= e($view->t('site.consent.policy')) ?></a><?php endif; ?></p>
    <div class="cookie__actions">
      <button class="btn btn--outline" type="submit" name="analytics" value="0"><?= e($view->t('site.consent.necessary')) ?></button>
      <button class="btn btn--primary" type="submit" name="analytics" value="1"><?= e($view->t('site.consent.accept')) ?></button>
    </div>
  </form>
</section>
