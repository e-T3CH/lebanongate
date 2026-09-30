<?php
/**
 * Footer: logo and short description, social links, quick links, contact details, newsletter sign-up, legal links,
 * copyright and the developer credit ("Developed by" + the E-5HOP logo, linked).
 *
 * @var \Gate\Core\View $view
 * @var array{logo: string|null, siteName: string, homeHref: string, about: string, socials: list<array{network: string, icon: string, url: string, label: string}>, links: list<array{label: string, href: string}>, contact: list<array{icon: string, text: string, href: string|null, ltr?: bool}>, newsletter: array{action: string, token: string, return: string}|null, legal: list<array{label: string, href: string}>, copyright: string, credit: array{label: string, logo: string, url: string, name: string}} $footer
 */
?>
<footer class="footer">
  <div class="wrap footer__cols">
    <div class="footer__about">
      <?= $view->render('site/parts/logo', ['logo' => $footer['logo'], 'siteName' => $footer['siteName'], 'href' => $footer['homeHref'], 'variant' => 'footer']) ?>
      <p><?= e($footer['about']) ?></p>
<?php if ($footer['socials'] !== []): ?>
      <ul class="social social--footer" aria-label="<?= e_attr($view->t('site.social.title')) ?>">
<?php foreach ($footer['socials'] as $social): ?>
        <li><a href="<?= e_url($social['url']) ?>" rel="noopener" target="_blank" aria-label="<?= e_attr($social['label']) ?>"><?= svg_icon($social['network']) ?></a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
    <nav class="footer__col" aria-labelledby="footer-links">
      <h2 class="footer__title" id="footer-links"><?= e($view->t('site.footer.links')) ?></h2>
      <ul>
<?php foreach ($footer['links'] as $link): ?>
        <li><a href="<?= e_url($link['href']) ?>"><?= e($link['label']) ?></a></li>
<?php endforeach; ?>
      </ul>
    </nav>
    <div class="footer__col">
      <h2 class="footer__title"><?= e($view->t('site.footer.contact')) ?></h2>
      <ul class="footer__contact">
<?php foreach ($footer['contact'] as $item): ?>
        <li><?= svg_icon($item['icon']) ?><?php if ($item['href'] !== null): ?><a href="<?= e_url($item['href']) ?>"<?= !empty($item['ltr']) ? ' dir="ltr"' : '' ?><?= str_starts_with($item['href'], 'https://') ? ' rel="noopener" target="_blank"' : '' ?>><?= e($item['text']) ?></a><?php else: ?><span<?= !empty($item['ltr']) ? ' dir="ltr"' : '' ?>><?= e($item['text']) ?></span><?php endif; ?></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php if ($footer['newsletter'] !== null): ?>
    <div class="footer__col">
      <h2 class="footer__title" id="newsletter-title"><?= e($view->t('site.newsletter.title')) ?></h2>
      <p><?= e($view->t('site.newsletter.text')) ?></p>
      <form class="newsletter" method="post" action="<?= e_url($footer['newsletter']['action']) ?>" aria-labelledby="newsletter-title">
        <?= $view->csrfField() ?>
        <input type="hidden" name="return" value="<?= e_attr($footer['newsletter']['return']) ?>">
        <input type="hidden" name="<?= e_attr(\Gate\Security\SpamGuard::TIMESTAMP) ?>" value="<?= e_attr($footer['newsletter']['token']) ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="<?= e_attr(\Gate\Security\SpamGuard::HONEYPOT) ?>" tabindex="-1" autocomplete="off"></label></div>
        <div class="newsletter__row">
          <label class="sr-only" for="newsletter-email"><?= e($view->t('site.newsletter.email')) ?></label>
          <input id="newsletter-email" type="email" name="email" required autocomplete="email" dir="ltr" placeholder="<?= e_attr($view->t('site.newsletter.placeholder')) ?>">
          <button type="submit" class="newsletter__btn"><?= e($view->t('site.newsletter.subscribe')) ?></button>
        </div>
        <label class="newsletter__consent"><input type="checkbox" name="consent" value="1" required> <span><?= e($view->t('site.newsletter.consent_label')) ?></span></label>
      </form>
    </div>
<?php endif; ?>
  </div>
  <div class="footer__bottom">
    <div class="wrap footer__bar">
      <span class="footer__copy"><?= e($footer['copyright']) ?></span>
      <div class="footer__end">
        <nav aria-label="<?= e_attr($view->t('site.footer.legal')) ?>"><ul class="footer__legal">
<?php foreach ($footer['legal'] as $link): ?>
          <li><a href="<?= e_url($link['href']) ?>"><?= e($link['label']) ?></a></li>
<?php endforeach; ?>
        </ul></nav>
        <span class="credit"><span><?= e($footer['credit']['label']) ?></span>
          <a href="<?= e_url($footer['credit']['url']) ?>" target="_blank" rel="noopener" dir="ltr"><img src="<?= e_attr($view->asset($footer['credit']['logo'])) ?>" width="107" height="20" alt="<?= e_attr($footer['credit']['name']) ?>"></a>
        </span>
      </div>
    </div>
  </div>
</footer>
