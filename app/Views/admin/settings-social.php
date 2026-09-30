<?php
/**
 * Settings → Social media (approved screen admin-security.html, second card): one row per network with its URL and
 * where the link appears.
 *
 * @var \Gate\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var list<array{network: string, label: string, url: string, header: bool, footer: bool}> $networks
 * @var array<string, string> $errors
 * @var string $adminPath
 */
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/settings/social') ?>" novalidate>
  <?= $view->csrfField() ?>
  <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.settings.social_title')) ?></h2><span class="muted"><?= e($view->t('admin.settings.social_desc')) ?></span></div>
<?php foreach ($networks as $network): ?>
  <div class="social-row flex gap-20">
    <?= $view->component('input', ['name' => 'url_' . $network['network'], 'label' => $network['label'], 'value' => $network['url'], 'error' => $errors['url_' . $network['network']] ?? null, 'placeholder' => $network['network'] === 'whatsapp' ? '+32 ...' : 'https://…']) ?>

    <div class="placement">
      <span class="placement__cap"><?= e($view->t('admin.settings.header')) ?></span>
      <?= $view->component('toggle', ['name' => 'header_' . $network['network'], 'label' => $network['label'] . ' — ' . $view->t('admin.settings.header'), 'checked' => $network['header']]) ?>

    </div>
    <div class="placement">
      <span class="placement__cap"><?= e($view->t('admin.settings.footer')) ?></span>
      <?= $view->component('toggle', ['name' => 'footer_' . $network['network'], 'label' => $network['label'] . ' — ' . $view->t('admin.settings.footer'), 'checked' => $network['footer']]) ?>

    </div>
  </div>
<?php endforeach; ?>
  <div class="actions">
    <?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/settings/social']) ?>

    <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

  </div>
</form>
