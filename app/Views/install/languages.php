<?php
/**
 * @var \Gate\Core\View $view
 * @var list<string> $enabled
 * @var string $default
 * @var list<string> $errors translation keys
 * @var string|null $detail
 */

use Gate\Core\Html;

$names = ['en' => ['English', 'English'], 'ar' => ['العربية', 'Arabic'], 'fr' => ['Français', 'French']];
$rows = [];
foreach (\Gate\I18n\LanguageRules::SUPPORTED as $code) {
    $rows[] = [
        'language' => Html::trusted('<span class="lang-cell"><span class="lang-code">' . e(strtoupper($code)) . '</span><span class="person__col"><span class="person__name" lang="' . e_attr($code) . '">' . e($names[$code][0]) . '</span><span class="person__date">' . e($names[$code][1]) . '</span></span></span>'),
        'enabled' => $view->component('toggle', ['name' => 'languages[]', 'value' => $code, 'uncheckedValue' => null, 'label' => $view->t('install.languages.enable', ['language' => $names[$code][0]]), 'checked' => in_array($code, $enabled, true)]),
        'default' => Html::trusted('<label class="radio' . ($default === $code ? ' is-default' : '') . '"><input type="radio" name="default_language" value="' . e_attr($code) . '"' . ($default === $code ? ' checked' : '') . '>' . e($view->t('install.languages.default')) . '</label>'),
    ];
}
$messages = array_map(static fn (string $key): string => $view->t($key), $errors);
if ($detail !== null) {
    $messages[] = $detail;
}
?>
<div class="card-intro card-intro--flush">
  <h1 class="atop__title"><?= e($view->t('install.languages.title')) ?></h1>
  <span class="muted"><?= e($view->t('install.languages.intro')) ?></span>
</div>
<?php if ($errors !== []): ?>
<?= $view->component('form-errors', ['messages' => $messages, 'icon' => 'fa-solid fa-globe']) ?>

<?php endif; ?>
<form method="post" action="/install/languages" novalidate>
  <?= $view->csrfField() ?>
  <?= $view->component('data-table', ['columns' => [
      ['key' => 'language', 'label' => $view->t('install.languages.language')],
      ['key' => 'enabled', 'label' => $view->t('install.languages.enabled')],
      ['key' => 'default', 'label' => $view->t('install.languages.default')],
  ], 'rows' => $rows]) ?>

  <p class="muted install-note"><?= e($view->t('install.languages.note')) ?></p>
  <div class="actions"><?= $view->component('button', ['label' => $view->t('install.back'), 'variant' => 'admin-secondary', 'href' => '/install/admin']) ?><?= $view->component('button', ['label' => $view->t('install.languages.install'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
</form>
