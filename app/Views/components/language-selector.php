<?php
/**
 * Language selector: desktop pill + listbox panel, or the mobile segmented control (drawer).
 * Only enabled languages are listed; nothing is rendered when fewer than two are enabled.
 * Options navigate to data-href (the same page in that language) through ui.js.
 * Parameters: Components::SPECS['language-selector'].
 *
 * @var \Gate\Core\View $view
 * @var array{languages: list<array{code: string, name: string, english: string, href?: ?string, current?: bool, default?: bool, enabled?: bool}>, variant: string, id: string, open: bool, class: string, attrs: array<string, string|int|bool|null>} $p
 */

use Gate\Core\Props;

$languages = [];
foreach ($p['languages'] as $l) {
    if (!is_array($l) || !is_string($l['code'] ?? null) || !is_string($l['name'] ?? null) || !is_string($l['english'] ?? null)) {
        throw new \InvalidArgumentException('language-selector: code, name and english are required');
    }
    if (($l['enabled'] ?? true) === true) {
        $languages[] = $l;
    }
}
if (count($languages) < 2) {
    return;
}
$current = $languages[0];
foreach ($languages as $l) {
    if (($l['current'] ?? false) === true) {
        $current = $l;
    }
}
$option = static function (array $l): string {
    $href = is_string($l['href'] ?? null) ? ' data-href="' . e_url($l['href']) . '"' : '';
    return 'data-lang-option="' . e_attr(strtoupper((string) $l['code'])) . '" data-lang-label="' . e_attr((string) $l['name']) . '"' . $href;
};
?>
<?php if ($p['variant'] === 'desktop'): ?>
<div<?= $p['class'] !== '' ? ' class="' . e_attr($p['class']) . '"' : '' ?> data-lang<?= Props::attrs($p['attrs']) ?>>
      <button class="langbtn" type="button" aria-haspopup="listbox" aria-expanded="<?= $p['open'] ? 'true' : 'false' ?>" aria-controls="<?= e_attr($p['id']) ?>" aria-label="<?= e_attr($view->t('ui.language.current', ['name' => $current['name']])) ?>"><?= $view->component('icon', ['icon' => 'fa-solid fa-globe', 'class' => 'ic-accent']) ?> <span data-lang-code><?= e(strtoupper($current['code'])) ?></span> <span class="ic-chev-wrap"><?= $view->component('icon', ['icon' => 'fa-solid fa-chevron-down', 'size' => '14', 'class' => 'ic-chev']) ?></span></button>
      <div class="<?= e_attr(Props::classes('langdd m-pop', $p['open'] ? 'is-open' : null)) ?>" role="listbox" aria-label="<?= e_attr($view->t('ui.language.label')) ?>" id="<?= e_attr($p['id']) ?>" tabindex="-1">
        <div class="langdd__cap"><?= e($view->t('ui.language.caption')) ?></div>
<?php foreach ($languages as $l): ?>
        <button type="button" role="option" aria-selected="<?= $l['code'] === $current['code'] ? 'true' : 'false' ?>" <?= $option($l) ?>>
          <span class="code"><?= e(strtoupper($l['code'])) ?></span>
          <span class="langdd__name"><span lang="<?= e_attr($l['code']) ?>"><?= e($l['name']) ?></span><span class="langdd__sub"><?= e(($l['default'] ?? false) === true ? $view->t('ui.language.default') : $l['english']) ?></span></span>
          <span class="langdd__chk"><?= $view->component('icon', ['icon' => 'fa-solid fa-check']) ?></span>
        </button>
<?php endforeach; ?>
      </div>
    </div>
<?php else: ?>
<div class="<?= e_attr(Props::classes('mnav__lang', $p['class'])) ?>"<?= Props::attrs($p['attrs']) ?>>
    <span class="mnav__cap"><?= e($view->t('ui.language.caption')) ?></span>
    <div class="segmented" role="radiogroup" aria-label="<?= e_attr($view->t('ui.language.label')) ?>" data-segmented>
<?php foreach ($languages as $l): ?>
      <button type="button" role="radio" aria-checked="<?= $l['code'] === $current['code'] ? 'true' : 'false' ?>" <?= $option($l) ?> lang="<?= e_attr($l['code']) ?>"><?= e(strtoupper($l['code'])) ?></button>
<?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
