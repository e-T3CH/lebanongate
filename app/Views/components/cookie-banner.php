<?php
/**
 * Cookie consent banner: short explanation, link to the cookie policy, "Accept analytics" and "Only necessary" with
 * equal weight. A plain POST form (works without JavaScript); the choice is stored in the bm_consent cookie.
 * Parameters: Components::SPECS['cookie-banner'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{action: string, returnTo: string, policyHref: string, open: bool} $p
 */
?>
<div class="cookie" id="cookie-consent" role="region" aria-labelledby="cookie-consent-title"<?= $p['open'] ? '' : ' hidden' ?>>
  <h2 class="cookie__title" id="cookie-consent-title"><?= e($view->t('site.consent.title')) ?></h2>
  <p class="cookie__text"><?= e($view->t('site.consent.text')) ?> <a href="<?= e_url($p['policyHref']) ?>"><?= e($view->t('site.consent.policy')) ?></a>.</p>
  <form class="cookie__actions" method="post" action="<?= e_url($p['action']) ?>">
    <?= $view->csrfField() ?><input type="hidden" name="return" value="<?= e_attr($p['returnTo']) ?>">
    <?= $view->component('button', ['label' => $view->t('site.consent.accept'), 'variant' => 'ghost', 'type' => 'submit', 'name' => 'analytics', 'value' => '1']) ?>

    <?= $view->component('button', ['label' => $view->t('site.consent.decline'), 'variant' => 'ghost', 'type' => 'submit', 'name' => 'analytics', 'value' => '0']) ?>

  </form>
</div>
