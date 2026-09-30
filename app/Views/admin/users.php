<?php
/**
 * System → Users: the team, the invite form and the open invitations.
 *
 * @var \Gate\Core\View $view
 * @var list<array{id: int, name: string, email: string, role: string, active: bool, two_factor: bool, last_login: string, is_you: bool}> $users
 * @var list<array{id: int, name: string, email: string, role: string, expires: string}> $invitations
 * @var int $inviteTtlHours
 * @var array<string, mixed> $old
 * @var array<string, string> $errors
 * @var string $adminPath
 */

use Gate\Core\Html;

$rows = [];
foreach ($users as $user) {
    $label = $user['name'] . ($user['is_you'] ? ' (' . $view->t('admin.users.you') . ')' : '');
    $rows[] = [
        'name' => Html::trusted('<a class="link-sm" href="' . e_url($adminPath . '/users/' . $user['id']) . '">' . e($label) . '</a>'),
        'email' => $user['email'],
        'role' => $view->t('admin.roles.' . $user['role']),
        'status' => $view->component('status-pill', ['label' => $view->t($user['active'] ? 'admin.users.active' : 'admin.users.inactive'), 'tone' => $user['active'] ? 'confirmed' : 'neutral', 'dot' => $user['active']]),
        'twofa' => $user['two_factor'] ? $view->component('icon', ['icon' => 'fa-solid fa-check', 'size' => '14', 'class' => 'ic-accent']) : '—',
        'login' => $user['last_login'],
    ];
}
$inviteRows = [];
foreach ($invitations as $invitation) {
    $inviteRows[] = [
        'name' => $invitation['name'],
        'email' => $invitation['email'],
        'role' => $view->t('admin.roles.' . $invitation['role']),
        'expires' => $invitation['expires'],
        'actions' => $view->component('row-actions', ['actions' => [
            ['action' => $adminPath . '/users/invitations/' . $invitation['id'] . '/resend', 'label' => $view->t('admin.users.resend'), 'icon' => 'fa-regular fa-paper-plane'],
            ['action' => $adminPath . '/users/invitations/' . $invitation['id'] . '/cancel', 'label' => $view->t('admin.users.cancel_invite'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.users.cancel_invite_confirm'), 'confirmTitle' => $view->t('admin.users.cancel_invite')],
        ]]),
    ];
}
?>
<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.users.card_title')) ?></h2></div>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.users.card_title'),
        'columns' => [
            ['key' => 'name', 'label' => $view->t('admin.users.name'), 'class' => 't-strong'],
            ['key' => 'email', 'label' => $view->t('admin.users.email'), 'class' => 't-muted'],
            ['key' => 'role', 'label' => $view->t('admin.users.role')],
            ['key' => 'status', 'label' => $view->t('admin.users.status')],
            ['key' => 'twofa', 'label' => $view->t('admin.users.two_factor')],
            ['key' => 'login', 'label' => $view->t('admin.users.last_login'), 'class' => 't-muted'],
        ],
        'rows' => $rows,
        'empty' => $view->component('empty-state', ['title' => $view->t('admin.users.empty_title'), 'text' => $view->t('admin.users.empty_text'), 'icon' => 'fa-solid fa-user-group']),
    ]) ?>

  </div>
  <div class="side-400">
    <form class="acard panel-fields" method="post" action="<?= e_url($adminPath . '/users/invite') ?>" novalidate>
      <?= $view->csrfField() ?>
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.users.invite_title')) ?></h2><span class="muted"><?= e($view->t('admin.users.invite_desc', ['hours' => $inviteTtlHours])) ?></span></div>
      <?= $view->component('input', ['name' => 'name', 'label' => $view->t('admin.users.name'), 'value' => is_scalar($old['name'] ?? null) ? (string) $old['name'] : '', 'error' => $errors['name'] ?? null, 'required' => true]) ?>

      <?= $view->component('input', ['name' => 'email', 'type' => 'email', 'label' => $view->t('admin.users.email'), 'value' => is_scalar($old['email'] ?? null) ? (string) $old['email'] : '', 'error' => $errors['email'] ?? null, 'required' => true]) ?>

      <?= $view->component('select', ['name' => 'role', 'id' => 'invite-role', 'label' => $view->t('admin.users.role'), 'value' => is_scalar($old['role'] ?? null) ? (string) $old['role'] : 'editor', 'error' => $errors['role'] ?? null, 'options' => [
          ['value' => 'editor', 'label' => $view->t('admin.roles.editor')],
          ['value' => 'admin', 'label' => $view->t('admin.roles.admin')],
      ]]) ?>

      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.users.invite_button'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-regular fa-paper-plane', 'iconPosition' => 'start']) ?></div>
    </form>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.users.pending_title')) ?></h2><span class="muted"><?= e($view->t('admin.users.pending_desc')) ?></span></div>
<?php if ($inviteRows === []): ?>
      <span class="muted"><?= e($view->t('admin.users.no_invitations')) ?></span>
<?php else: ?>
      <?= $view->component('data-table', [
          'caption' => $view->t('admin.users.pending_title'),
          'columns' => [
              ['key' => 'name', 'label' => $view->t('admin.users.name'), 'class' => 't-strong'],
              ['key' => 'email', 'label' => $view->t('admin.users.email'), 'class' => 't-muted'],
              ['key' => 'role', 'label' => $view->t('admin.users.role')],
              ['key' => 'expires', 'label' => $view->t('admin.users.expires_on'), 'class' => 't-muted'],
              ['key' => 'actions', 'label' => $view->t('admin.users.status'), 'hideLabel' => true],
          ],
          'rows' => $inviteRows,
      ]) ?>

<?php endif; ?>
    </div>
  </div>
</div>
