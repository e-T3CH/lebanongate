<?php
/**
 * Content → Pages → home page → one section: the texts of that section in one language.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, type: string, page_id: int, locked: bool, enabled: bool} $section
 * @var string $lang
 * @var array<string, string> $values
 * @var list<array{label: string, href: string, active: bool}> $tabs
 * @var bool $canEdit
 * @var string $adminPath
 */
?>
<?= $view->component('tabs', ['label' => $view->t('admin.content.language'), 'items' => $tabs]) ?>

<form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/pages/' . $section['page_id'] . '/sections/' . $section['id']) ?>" novalidate>
  <?= $view->csrfField() ?><input type="hidden" name="lang" value="<?= e_attr($lang) ?>">
  <div class="sec-card__head">
    <h2 class="h3"><?= e($view->t('admin.sections.' . $section['type'])) ?> · <?= e(strtoupper($lang)) ?></h2>
    <?= $view->component('status-pill', ['label' => $view->t($section['enabled'] ? 'admin.lists.visible' : 'admin.users.inactive'), 'tone' => $section['enabled'] ? 'confirmed' : 'neutral', 'dot' => $section['enabled']]) ?>

  </div>
  <div class="fields-2 fields-2--sec">
    <?= $view->component('input', ['name' => 'label', 'label' => $view->t('admin.pages.label'), 'value' => $values['label'], 'disabled' => !$canEdit]) ?>

    <?= $view->component('input', ['name' => 'title', 'label' => $view->t('admin.pages.page_title'), 'value' => $values['title'], 'disabled' => !$canEdit]) ?>

    <?= $view->component('input', ['name' => 'highlight', 'label' => $view->t('admin.pages.highlight'), 'value' => $values['highlight'], 'hint' => $view->t('admin.pages.highlight_hint'), 'disabled' => !$canEdit]) ?>

  </div>
  <?= $view->component('textarea', ['name' => 'intro', 'id' => 'section-intro', 'label' => $view->t('admin.pages.intro'), 'value' => $values['intro'], 'rows' => 3, 'maxlength' => 2000, 'disabled' => !$canEdit]) ?>

  <div class="actions">
    <?= $view->component('button', ['label' => $view->t('admin.common.back'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/pages/' . $section['page_id'] . '?lang=' . $lang]) ?>
<?php if ($canEdit): ?>
    <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?>

<?php endif; ?>
  </div>
</form>
