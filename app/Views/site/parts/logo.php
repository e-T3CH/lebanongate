<?php
/**
 * The logo: the image uploaded in Appearance, else the default mark (cedar + name) of the approved mockup.
 *
 * @var \Gate\Core\View $view
 * @var string|null $logo
 * @var string $siteName
 * @var string $href
 * @var string $variant header|footer
 */
?>
<a class="brand brand--<?= e_attr($variant) ?>" href="<?= e_url($href) ?>" aria-label="<?= e_attr($view->t('site.home_link', ['site' => $siteName])) ?>">
<?php if ($logo !== null): ?>
  <img class="brand__img" src="<?= e_url($logo) ?>" alt="<?= e_attr($siteName) ?>">
<?php else: ?>
  <span class="brand__mark"><?= svg_icon('cedar', 'brand__cedar') ?></span>
  <span class="brand__text"><b>GATE</b><span>LEBANON</span></span>
<?php endif; ?>
</a>
