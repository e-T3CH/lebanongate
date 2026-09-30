<?php
/**
 * System → Appearance: logo and favicon from the media library, the colour tokens with a live contrast check,
 * the corner radii and the motion setting, plus a reset to the approved design.
 *
 * @var \Gate\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var array<string, list<array{token: string, label: string, value: string, default: string}>> $colors
 * @var list<array{token: string, label: string, value: string}> $radii
 * @var list<array{0: string, 1: string}> $contrastPairs
 * @var list<array{value: string, label: string}> $images
 * @var string $logo
 * @var string $logoDark
 * @var string $favicon
 * @var bool $motionEnabled
 * @var string $motionIntensity
 * @var array<string, string> $errors
 * @var string $adminPath
 */
$pairs = [];
foreach ($contrastPairs as [$text, $background]) {
    $pairs[] = ['text' => $text, 'background' => $background];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<form class="flex gap-20 agrow" method="post" action="<?= e_url($adminPath . '/appearance') ?>" novalidate>
  <?= $view->csrfField() ?>
  <div class="acard agrow sec-card">
    <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.appearance.colors_title')) ?></h2><span class="muted"><?= e($view->t('admin.appearance.colors_desc')) ?></span></div>
<?php foreach ($colors as $group => $tokens): ?>
    <h3 class="h4 group-title"><?= e($view->t('admin.appearance.group_' . $group)) ?></h3>
    <div class="fields-3 fields-3--sec">
<?php foreach ($tokens as $token): ?>
      <?= $view->component('input', [
          'name' => 'theme_' . $token['token'],
          'id' => 'theme-' . $token['token'],
          'label' => $token['label'],
          'value' => $token['value'],
          'error' => $errors['theme_' . $token['token']] ?? null,
          'hint' => $token['value'] === $token['default'] ? null : $view->t('admin.content.draft'),
          'color' => true,
          'attrs' => ['data-color-token' => $token['token']],
      ]) ?>

<?php endforeach; ?>
    </div>
<?php endforeach; ?>
    <ul class="contrast" data-contrast>
<?php foreach ($pairs as $pair): ?>
      <li class="contrast__row" data-contrast-pair data-text="<?= e_attr($pair['text']) ?>" data-background="<?= e_attr($pair['background']) ?>">
        <span class="contrast__label"><?= e($view->t('admin.appearance.contrast_on', ['text' => $view->t('admin.appearance.token_' . str_replace('-', '_', $pair['text'])), 'background' => $view->t('admin.appearance.token_' . str_replace('-', '_', $pair['background']))])) ?></span>
        <span class="contrast__result" data-contrast-result data-ok="<?= e_attr($view->t('admin.appearance.contrast_ok', ['ratio' => ':ratio'])) ?>" data-low="<?= e_attr($view->t('admin.appearance.contrast_low', ['ratio' => ':ratio'])) ?>"></span>
      </li>
<?php endforeach; ?>
    </ul>
    <div class="sec-card__intro"><h2 class="h3"><?= e($view->t('admin.appearance.radii_title')) ?></h2><span class="muted"><?= e($view->t('admin.appearance.radii_desc')) ?></span></div>
    <div class="fields-3 fields-3--sec">
<?php foreach ($radii as $radius): ?>
      <?= $view->component('input', ['name' => 'theme_' . $radius['token'], 'label' => $radius['label'], 'value' => $radius['value'], 'error' => $errors['theme_' . $radius['token']] ?? null]) ?>

<?php endforeach; ?>
    </div>
    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/appearance']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
  </div>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.appearance.images_title')) ?></h2><span class="muted"><?= e($view->t('admin.appearance.images_desc')) ?></span></div>
      <?= $view->component('select', ['name' => 'logo', 'id' => 'appearance-logo', 'label' => $view->t('admin.appearance.logo'), 'value' => $logo, 'options' => $images, 'error' => $errors['logo'] ?? null]) ?>

      <?= $view->component('select', ['name' => 'logo_dark', 'id' => 'appearance-logo-dark', 'label' => $view->t('admin.appearance.logo_dark'), 'value' => $logoDark, 'options' => $images, 'error' => $errors['logo_dark'] ?? null]) ?>

      <?= $view->component('select', ['name' => 'favicon', 'id' => 'appearance-favicon', 'label' => $view->t('admin.appearance.favicon'), 'value' => $favicon, 'options' => $images, 'error' => $errors['favicon'] ?? null]) ?>

    </div>
    <div class="acard acard--list">
      <h2 class="h3 acard__title"><?= e($view->t('admin.appearance.motion_title')) ?></h2>
      <?= $view->component('setting-row', ['name' => $view->t('admin.appearance.motion_enabled'), 'description' => $view->t('admin.appearance.motion_desc'), 'wide' => true, 'control' => $view->component('toggle', ['name' => 'motion_enabled', 'label' => $view->t('admin.appearance.motion_enabled'), 'checked' => $motionEnabled])]) ?>

      <div class="card-rows">
        <?= $view->component('select', ['name' => 'motion_intensity', 'id' => 'motion-intensity', 'label' => $view->t('admin.appearance.motion_intensity'), 'value' => $motionIntensity, 'options' => [
            ['value' => 'standard', 'label' => $view->t('admin.appearance.motion_standard')],
            ['value' => 'subtle', 'label' => $view->t('admin.appearance.motion_subtle')],
        ]]) ?>

      </div>
    </div>
  </div>
</form>
<div class="acard panel-fields">
  <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.appearance.reset')) ?></h2><span class="muted"><?= e($view->t('admin.appearance.reset_confirm')) ?></span></div>
  <?= $view->component('row-actions', ['actions' => [[
      'action' => $adminPath . '/appearance/reset',
      'label' => $view->t('admin.appearance.reset'),
      'icon' => 'fa-solid fa-rotate-right',
      'tone' => 'danger',
      'confirm' => $view->t('admin.appearance.reset_confirm'),
      'confirmTitle' => $view->t('admin.appearance.reset'),
  ]]]) ?>

</div>
