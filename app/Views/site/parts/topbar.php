<?php
/**
 * Top bar: contact details, social links and the language menu (a dropdown of the enabled languages).
 *
 * @var \Gate\Core\View $view
 * @var array{email: string, emailHref: string|null, phone: string, phoneHref: string|null, address: string, socials: list<array{network: string, icon: string, url: string, label: string}>, languages: list<array{code: string, name: string, href: string, current: bool, dir: string}>, currentLanguage: array{code: string, name: string}|null} $topbar
 */
?>
<div class="topbar">
  <div class="wrap topbar__row">
    <ul class="topbar__contact">
<?php if ($topbar['email'] !== ''): ?>
      <li><?= svg_icon('mail') ?><?php if ($topbar['emailHref'] !== null): ?><a href="<?= e_url($topbar['emailHref']) ?>" dir="ltr"><?= e($topbar['email']) ?></a><?php else: ?><span dir="ltr"><?= e($topbar['email']) ?></span><?php endif; ?></li>
<?php endif; ?>
<?php if ($topbar['phone'] !== ''): ?>
      <li><?= svg_icon('phone') ?><?php if ($topbar['phoneHref'] !== null): ?><a href="<?= e_url($topbar['phoneHref']) ?>" dir="ltr"><?= e($topbar['phone']) ?></a><?php else: ?><span dir="ltr"><?= e($topbar['phone']) ?></span><?php endif; ?></li>
<?php endif; ?>
<?php if ($topbar['address'] !== ''): ?>
      <li class="topbar__address"><?= svg_icon('pin') ?><span><?= e($topbar['address']) ?></span></li>
<?php endif; ?>
    </ul>
    <div class="topbar__end">
<?php if ($topbar['socials'] !== []): ?>
      <ul class="social" aria-label="<?= e_attr($view->t('site.social.title')) ?>">
<?php foreach ($topbar['socials'] as $social): ?>
        <li><a href="<?= e_url($social['url']) ?>" rel="noopener" target="_blank" aria-label="<?= e_attr($social['label']) ?>"><?= svg_icon($social['network']) ?></a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php if ($topbar['languages'] !== [] && $topbar['currentLanguage'] !== null): ?>
      <div class="lang" data-lang-menu>
        <button type="button" class="lang__btn" aria-haspopup="true" aria-expanded="false" aria-controls="lang-menu" aria-label="<?= e_attr($view->t('site.language.label', ['name' => $topbar['currentLanguage']['name']])) ?>">
          <?= svg_icon('globe') ?><span><?= e($topbar['currentLanguage']['name']) ?></span><?= svg_icon('caret', 'ic ic--caret') ?>
        </button>
        <ul class="lang__menu" id="lang-menu" role="menu" aria-label="<?= e_attr($view->t('site.language.choose')) ?>">
<?php foreach ($topbar['languages'] as $language): ?>
          <li role="none"><a role="menuitemradio" aria-checked="<?= $language['current'] ? 'true' : 'false' ?>" href="<?= e_url($language['href']) ?>" hreflang="<?= e_attr($language['code']) ?>" lang="<?= e_attr($language['code']) ?>" dir="<?= e_attr($language['dir']) ?>"><?= e($language['name']) ?><?php if ($language['current']): ?><?= svg_icon('check', 'ic ic--check') ?><?php endif; ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>
    </div>
  </div>
</div>
