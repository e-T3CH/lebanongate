<?php
/**
 * System → Users → one user: name, role, activation and a 2FA reset. The last active administrator keeps the
 * administrator role and stays active, so nobody can lock everyone out.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, name: string, email: string, role: string, active: bool, two_factor: bool, last_login: string} $editUser
 * @var bool $isLastAdmin
 * @var bool $isYou
 * @var array<string, string> $errors
 * @var string $adminPath
 */
?>
<?= $view->component('tabs', ['label' => $view->t('admin.users.title'), 'items' => [
    ['label' => $view->t('admin.users.card_title'), 'href' => $adminPath . '/users'],
    ['label' => $editUser['name'], 'href' => $adminPath . '/users/' . $editUser['id'], 'active' => true],
]]) ?>

<div class="flex gap-20 agrow">
  <form class="acard agrow sec-card" method="post" action="<?= e_url($adminPath . '/users/' . $editUser['id']) ?>" novalidate>
    <?= $view->csrfField() ?>
    <div class="sec-card__head">
      <h2 class="h3"><?= e($view->t('admin.users.account_title')) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t($editUser['active'] ? 'admin.users.active' : 'admin.users.inactive'), 'tone' => $editUser['active'] ? 'confirmed' : 'neutral', 'dot' => $editUser['active']]) ?>

    </div>
    <div class="fields-2 fields-2--sec">
      <?= $view->component('input', ['name' => 'name', 'label' => $view->t('admin.users.name'), 'value' => $editUser['name'], 'error' => $errors['name'] ?? null, 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'email_display', 'label' => $view->t('admin.users.email'), 'value' => $editUser['email'], 'disabled' => true, 'hint' => $view->t('admin.profile.email_hint')]) ?>

      <?= $view->component('select', ['name' => 'role', 'id' => 'user-role', 'label' => $view->t('admin.users.role'), 'value' => $editUser['role'], 'error' => $errors['role'] ?? null, 'disabled' => $isLastAdmin, 'hint' => $isLastAdmin ? $view->t('admin.users.last_admin') : null, 'options' => [
          ['value' => 'editor', 'label' => $view->t('admin.roles.editor')],
          ['value' => 'admin', 'label' => $view->t('admin.roles.admin')],
      ]]) ?>

      <?= $view->component('input', ['name' => 'last_login', 'label' => $view->t('admin.users.last_login'), 'value' => $editUser['last_login'], 'disabled' => true]) ?>

    </div>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.discard'), 'variant' => 'admin-secondary', 'href' => $adminPath . '/users']) ?><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
  </form>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.users.status')) ?></h2><span class="muted"><?= e($view->t($editUser['active'] ? 'admin.users.deactivate_confirm' : 'admin.users.empty_text')) ?></span></div>
      <?= $view->component('row-actions', ['actions' => array_values(array_filter([
          $editUser['active'] && !$isLastAdmin && !$isYou ? ['action' => $adminPath . '/users/' . $editUser['id'] . '/active', 'label' => $view->t('admin.users.deactivate'), 'icon' => 'fa-solid fa-user-group', 'tone' => 'danger', 'fields' => ['active' => '0'], 'confirm' => $view->t('admin.users.deactivate_confirm'), 'confirmTitle' => $view->t('admin.users.deactivate')] : null,
          !$editUser['active'] ? ['action' => $adminPath . '/users/' . $editUser['id'] . '/active', 'label' => $view->t('admin.users.activate'), 'icon' => 'fa-solid fa-check', 'fields' => ['active' => '1']] : null,
          $editUser['two_factor'] ? ['action' => $adminPath . '/users/' . $editUser['id'] . '/two-factor-reset', 'label' => $view->t('admin.users.reset_two_factor'), 'icon' => 'fa-solid fa-shield-halved', 'tone' => 'danger', 'confirm' => $view->t('admin.users.reset_two_factor_confirm'), 'confirmTitle' => $view->t('admin.users.reset_two_factor')] : null,
      ]))]) ?>

<?php if ($isLastAdmin): ?>
      <?= $view->component('notice', ['title' => $view->t('admin.users.last_admin')]) ?>

<?php endif; ?>
    </div>
  </div>
</div>
