<?php
/**
 * "Developed by" + the E-5HOP logo (dark letters, for light backgrounds), linked to the developer's website.
 * Used on the admin panel, the sign-in and installer screens and the plain error page; the public site has the
 * light version in its footer.
 *
 * @var \Gate\Core\View $view
 */

use Gate\Site\Brand;

?>
<p class="dev-credit"><span><?= e($view->t('site.footer.developed_by')) ?></span>
  <a href="<?= e_url(Brand::CREDIT_URL) ?>" target="_blank" rel="noopener" dir="ltr"><img src="<?= e_attr($view->asset(Brand::CREDIT_LOGO_DARK)) ?>" width="96" height="18" alt="E-5HOP"></a>
</p>
