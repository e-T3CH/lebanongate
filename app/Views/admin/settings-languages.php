<?php
/**
 * Settings → Languages (approved screen admin-languages.html) with live data: which languages visitors can choose,
 * which one is the default, and how much of the interface is translated. The admin panel language is chosen apart from
 * those: every supported language is offered, also one the website does not show.
 *
 * @var \BMMatic\Core\View $view
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var list<array{code: string, name: string, native: string, enabled: bool, default: bool, progress: int}> $languages
 * @var bool $detectBrowser
 * @var bool $selectorInHeader
 * @var string $adminLanguage
 * @var mixed $error
 * @var string $adminPath
 */

use BMMatic\Core\Html;

$rows = [];
foreach ($languages as $language) {
    $code = $language['code'];
    $rows[] = [
        'language' => Html::trusted('<span class="lang-cell"><span class="lang-code">' . e(strtoupper($code)) . '</span><span class="person__col"><span class="person__name">' . e($language['native']) . '</span><span class="person__date">' . e($language['name']) . '</span></span></span>'),
        'enabled' => $view->component('toggle', [
            'name' => 'enabled[]',
            'value' => $code,
            'uncheckedValue' => null,
            'label' => $view->t('admin.settings.enabled') . ' ' . $language['name'],
            'checked' => $language['enabled'],
            'disabled' => $language['default'],
        ]),
        'default' => Html::trusted('<label class="radio' . ($language['default'] ? ' is-default' : '') . '"><input type="radio" name="default_language" value="' . e_attr($code) . '"' . ($language['default'] ? ' checked' : '') . '> ' . e($view->t($language['default'] ? 'admin.settings.default' : 'admin.settings.set_default')) . '</label>'),
        'progress' => $view->component('progress', ['value' => $language['progress'], 'label' => $view->t('admin.settings.translated') . ' ' . $language['name']]),
    ];
}
?>
<?= $view->component('tabs', ['label' => $view->t('admin.settings.title'), 'items' => $tabs]) ?>

<form class="flex gap-20 agrow" method="post" action="<?= e_url($adminPath . '/settings/languages') ?>">
  <?= $view->csrfField() ?>
  <div class="acard agrow col">
    <div class="card-intro"><h2 class="h3"><?= e($view->t('admin.settings.languages_title')) ?></h2><span class="muted"><?= e($view->t('admin.settings.languages_desc')) ?></span></div>
<?php if (is_string($error)): ?>
    <?= $view->component('notice', ['title' => $error, 'role' => 'alert']) ?>

<?php endif; ?>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.settings.languages_title'),
        'columns' => [
            ['key' => 'language', 'label' => $view->t('admin.settings.language')],
            ['key' => 'enabled', 'label' => $view->t('admin.settings.enabled')],
            ['key' => 'default', 'label' => $view->t('admin.settings.default')],
            ['key' => 'progress', 'label' => $view->t('admin.settings.translated')],
        ],
        'rows' => $rows,
    ]) ?>

    <div class="card-rows">
      <?= $view->component('setting-row', ['name' => $view->t('admin.settings.detect_browser'), 'description' => $view->t('admin.settings.detect_browser_desc'), 'control' => $view->component('toggle', ['name' => 'detect_browser', 'label' => $view->t('admin.settings.detect_browser'), 'checked' => $detectBrowser])]) ?>

      <?= $view->component('setting-row', ['name' => $view->t('admin.settings.selector_in_header'), 'description' => $view->t('admin.settings.selector_in_header_desc'), 'control' => $view->component('toggle', ['name' => 'selector_in_header', 'label' => $view->t('admin.settings.selector_in_header'), 'checked' => $selectorInHeader])]) ?>

    </div>
    <div class="actions">
      <?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/settings/languages']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

    </div>
  </div>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.settings.admin_panel_title')) ?></h2><span class="muted"><?= e($view->t('admin.settings.admin_panel_desc')) ?></span></div>
      <?= $view->component('select', [
          'name' => 'admin_language',
          'id' => 'admin-language',
          'label' => $view->t('admin.settings.admin_language'),
          'value' => $adminLanguage,
          'options' => array_map(static fn (array $l): array => ['value' => $l['code'], 'label' => $l['native'], 'code' => strtoupper($l['code'])], array_values(array_filter($languages, static fn (array $l): bool => in_array($l['code'], \BMMatic\I18n\LanguageRules::SUPPORTED, true)))),
      ]) ?>

    </div>
  </div>
</form>
