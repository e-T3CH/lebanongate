<?php
/**
 * Settings → General: company details, contact details and the website switches.
 *
 * @var \Gate\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var array{online_booking: bool, mobile_dock: bool, maintenance_mode: bool} $toggles
 * @var string $adminPath
 */
$v = static fn (string $key): string => is_scalar($values[$key] ?? null) ? (string) $values[$key] : '';
$field = static fn (string $name, array $extra = []): string => (string) $view->component('input', ['name' => $name, 'label' => $view->t('admin.settings.' . $name), 'value' => $v($name), 'error' => $errors[$name] ?? null] + $extra);
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<form class="flex gap-20 agrow" method="post" action="<?= e_url($adminPath . '/settings/general') ?>" novalidate>
  <?= $view->csrfField() ?>
  <div class="acard agrow sec-card">
    <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.settings.company_title')) ?></h2><span class="muted"><?= e($view->t('admin.settings.company_desc')) ?></span></div>
    <div class="fields-2 fields-2--sec">
      <?= $field('site_name', ['required' => true]) ?>
      <?= $field('company_name') ?>
      <?= $field('vat') ?>
      <?= $field('street') ?>
      <?= $field('postcode') ?>
      <?= $field('city') ?>
      <?= $field('country') ?>
    </div>
    <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.settings.contact_title')) ?></h2><span class="muted"><?= e($view->t('admin.settings.contact_desc')) ?></span></div>
    <div class="fields-2 fields-2--sec">
      <?= $field('phone', ['type' => 'tel']) ?>
      <?= $field('whatsapp', ['type' => 'tel']) ?>
      <?= $field('email') ?>
      <?= $field('hours_weekdays') ?>
      <?= $field('hours_saturday') ?>
      <?= $field('latitude') ?>
      <?= $field('longitude') ?>
    </div>
    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/settings/general']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
  </div>
  <div class="side-400">
    <div class="acard acard--list">
      <h2 class="h3 acard__title"><?= e($view->t('admin.settings.website_title')) ?></h2>
<?php foreach (['online_booking', 'mobile_dock', 'maintenance_mode'] as $key): ?>
      <?= $view->component('setting-row', [
          'name' => $view->t('admin.settings.' . $key),
          'description' => $view->t('admin.settings.' . $key . '_desc'),
          'control' => $view->component('toggle', ['name' => $key, 'label' => $view->t('admin.settings.' . $key), 'checked' => $toggles[$key]]),
      ]) ?>

<?php endforeach; ?>
    </div>
  </div>
</form>
