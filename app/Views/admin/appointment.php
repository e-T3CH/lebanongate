<?php
/**
 * One appointment request: customer and vehicle details, the status flow and the internal notes.
 *
 * @var \BMMatic\Core\View $view
 * @var array{id: int, name: string, email: string, phone: string, car: string, gearbox: string, symptoms: string, lang: string, received: string, consent: string, status: string, unread: bool, mailto: string, telHref: string} $appointment
 * @var list<array{id: int, author: string, when: string, body: string}> $notes
 * @var list<string> $statuses
 * @var list<string> $statusEmails
 * @var bool $canManage
 * @var array<string, string> $errors
 * @var string $adminPath
 */
$base = $adminPath . '/appointments/' . $appointment['id'];
?>
<?= $view->component('tabs', ['label' => $view->t('admin.appointments.title'), 'items' => [
    ['label' => $view->t('admin.appointments.card_title'), 'href' => $adminPath . '/appointments'],
    ['label' => '#' . $appointment['id'], 'href' => $base, 'active' => true],
]]) ?>

<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head">
      <h2 class="h3"><?= e($appointment['name']) ?></h2>
      <?= $view->component('status-pill', ['label' => $view->t('admin.statuses.' . $appointment['status']), 'tone' => match ($appointment['status']) { 'new' => 'new', 'confirmed' => 'confirmed', 'diagnosis' => 'diagnosis', 'quoted' => 'quoted', 'cancelled' => 'danger', default => 'neutral' }]) ?>

    </div>
    <dl class="detail-list">
      <div><dt><?= e($view->t('admin.appointments.received')) ?></dt><dd><?= e($appointment['received']) ?></dd></div>
      <div><dt><?= e($view->t('site.form.phone')) ?></dt><dd><a class="link-sm" href="<?= e_url($appointment['telHref']) ?>"><?= e($appointment['phone']) ?></a></dd></div>
      <div><dt><?= e($view->t('site.form.email')) ?></dt><dd><a class="link-sm" href="<?= e_url($appointment['mailto']) ?>"><?= e($appointment['email']) ?></a></dd></div>
      <div><dt><?= e($view->t('admin.appointments.vehicle')) ?></dt><dd><?= e($appointment['car']) ?></dd></div>
      <div><dt><?= e($view->t('site.form.type')) ?></dt><dd><?= e($appointment['gearbox']) ?></dd></div>
      <div><dt><?= e($view->t('admin.appointments.language')) ?></dt><dd><?= e($appointment['lang']) ?></dd></div>
    </dl>
    <div class="detail-block">
      <h3 class="h4"><?= e($view->t('admin.appointments.symptoms')) ?></h3>
      <p class="detail-text"><?= nl2br(e($appointment['symptoms']), false) ?></p>
    </div>
    <span class="muted"><?= e($view->t('admin.appointments.consent', ['date' => $appointment['consent']])) ?></span>
  </div>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.appointments.status_title')) ?></h2><span class="muted"><?= e($view->t('admin.appointments.status_hint')) ?></span></div>
<?php if ($canManage): ?>
      <form class="panel-fields" method="post" action="<?= e_url($base . '/status') ?>">
        <?= $view->csrfField() ?>
        <?= $view->component('select', ['name' => 'status', 'id' => 'status', 'label' => $view->t('admin.appointments.change_status'), 'value' => $appointment['status'], 'options' => array_map(static fn (string $s): array => ['value' => $s, 'label' => $view->t('admin.statuses.' . $s) . (in_array($s, $statusEmails, true) ? ' ✉' : '')], $statuses)]) ?>

        <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
      </form>
      <?= $view->component('row-actions', ['actions' => [
          ['action' => $base . '/unread', 'label' => $view->t('admin.appointments.mark_unread'), 'icon' => 'fa-regular fa-envelope'],
      ]]) ?>

<?php endif; ?>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.appointments.reply'), 'variant' => 'admin-secondary', 'href' => $appointment['mailto'], 'icon' => 'fa-regular fa-paper-plane', 'iconPosition' => 'start']) ?></div>
    </div>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.appointments.notes_title')) ?></h2><span class="muted"><?= e($view->t('admin.appointments.notes_desc')) ?></span></div>
<?php if ($notes === []): ?>
      <span class="muted"><?= e($view->t('admin.appointments.notes_desc')) ?></span>
<?php else: ?>
      <ul class="notes">
<?php foreach ($notes as $note): ?>
        <li class="note">
          <div class="note__head"><span class="note__author"><?= e($note['author']) ?></span><span class="note__when muted"><?= e($note['when']) ?></span></div>
          <p class="note__body"><?= nl2br(e($note['body']), false) ?></p>
<?php if ($canManage): ?>
          <?= $view->component('row-actions', ['actions' => [
              ['action' => $base . '/notes/' . $note['id'] . '/delete', 'label' => $view->t('admin.appointments.delete_note'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.appointments.delete_note_confirm'), 'confirmTitle' => $view->t('admin.appointments.delete_note')],
          ]]) ?>

<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php if ($canManage): ?>
      <form class="panel-fields" method="post" action="<?= e_url($base . '/notes') ?>" novalidate>
        <?= $view->csrfField() ?>
        <?= $view->component('textarea', ['name' => 'body', 'id' => 'note-body', 'label' => $view->t('admin.appointments.add_note'), 'placeholder' => $view->t('admin.appointments.note_placeholder'), 'error' => $errors['body'] ?? null, 'rows' => 3, 'maxlength' => 2000]) ?>

        <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.appointments.add_note'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
      </form>
<?php endif; ?>
    </div>
  </div>
</div>
