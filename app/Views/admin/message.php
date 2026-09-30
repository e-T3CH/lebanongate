<?php
/**
 * One message: sender details, the message, internal notes, reply (mail program), mark unread, archive, delete.
 *
 * @var \Gate\Core\View $view
 * @var array{id: int, name: string, email: string, phone: string, organisation: string, subject: string, text: string, lang: string, received: string, consent: string, archived: bool, mailto: string, telHref: string|null} $message
 * @var list<array{id: int, author: string, when: string, body: string}> $notes
 * @var bool $canManage
 * @var array<string, string> $errors
 * @var string $adminPath
 */
$base = $adminPath . '/messages/' . $message['id'];
?>
<?= $view->component('tabs', ['label' => $view->t('admin.messages.title'), 'items' => [
    ['label' => $view->t('admin.messages.box_inbox'), 'href' => $adminPath . '/messages'],
    ['label' => '#' . $message['id'], 'href' => $base, 'active' => true],
]]) ?>

<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head">
      <h2 class="h3"><?= e($message['name']) ?></h2>
      <?= $view->component('status-pill', ['label' => $message['subject'], 'tone' => 'new']) ?>

    </div>
    <dl class="detail-list">
      <div><dt><?= e($view->t('admin.messages.received')) ?></dt><dd><?= e($message['received']) ?></dd></div>
      <div><dt><?= e($view->t('site.form.email')) ?></dt><dd><a class="link-sm" href="<?= e_url($message['mailto']) ?>"><?= e($message['email']) ?></a></dd></div>
<?php if ($message['phone'] !== ''): ?>
      <div><dt><?= e($view->t('site.form.phone')) ?></dt><dd><?php if ($message['telHref'] !== null): ?><a class="link-sm" href="<?= e_url($message['telHref']) ?>"><?= e($message['phone']) ?></a><?php else: ?><?= e($message['phone']) ?><?php endif; ?></dd></div>
<?php endif; ?>
<?php if ($message['organisation'] !== ''): ?>
      <div><dt><?= e($view->t('site.form.organisation')) ?></dt><dd><?= e($message['organisation']) ?></dd></div>
<?php endif; ?>
      <div><dt><?= e($view->t('admin.messages.language')) ?></dt><dd><?= e($message['lang']) ?></dd></div>
    </dl>
    <div class="detail-block">
      <h3 class="h4"><?= e($view->t('site.form.message')) ?></h3>
      <p class="detail-text" dir="auto"><?= nl2br(e($message['text']), false) ?></p>
    </div>
    <span class="muted"><?= e($view->t('admin.messages.consent', ['date' => $message['consent']])) ?></span>
  </div>
  <div class="side-400">
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.messages.actions')) ?></h2><span class="muted"><?= e($view->t('admin.messages.reply_hint')) ?></span></div>
      <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.messages.reply'), 'variant' => 'admin-primary', 'href' => $message['mailto'], 'icon' => 'fa-regular fa-paper-plane', 'iconPosition' => 'start']) ?></div>
<?php if ($canManage): ?>
      <?= $view->component('row-actions', ['actions' => array_values(array_filter([
          $message['archived'] ? null : ['action' => $base . '/unread', 'label' => $view->t('admin.messages.mark_unread'), 'icon' => 'fa-regular fa-envelope'],
          ['action' => $base . '/archive', 'label' => $view->t($message['archived'] ? 'admin.messages.restore' : 'admin.messages.archive'), 'icon' => 'fa-solid fa-box-archive'],
          ['action' => $base . '/delete', 'label' => $view->t('admin.messages.delete'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.messages.delete_confirm'), 'confirmTitle' => $view->t('admin.messages.delete')],
      ]))]) ?>

<?php endif; ?>
    </div>
    <div class="acard panel-fields">
      <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.messages.notes_title')) ?></h2><span class="muted"><?= e($view->t('admin.messages.notes_desc')) ?></span></div>
<?php if ($notes !== []): ?>
      <ul class="notes">
<?php foreach ($notes as $note): ?>
        <li class="note">
          <div class="note__head"><span class="note__author"><?= e($note['author']) ?></span><span class="note__when muted"><?= e($note['when']) ?></span></div>
          <p class="note__body"><?= nl2br(e($note['body']), false) ?></p>
<?php if ($canManage): ?>
          <?= $view->component('row-actions', ['actions' => [
              ['action' => $base . '/notes/' . $note['id'] . '/delete', 'label' => $view->t('admin.messages.delete_note'), 'icon' => 'fa-solid fa-xmark', 'tone' => 'danger', 'confirm' => $view->t('admin.messages.delete_note_confirm'), 'confirmTitle' => $view->t('admin.messages.delete_note')],
          ]]) ?>

<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
<?php if ($canManage): ?>
      <form class="panel-fields" method="post" action="<?= e_url($base . '/notes') ?>" novalidate>
        <?= $view->csrfField() ?>
        <?= $view->component('textarea', ['name' => 'body', 'id' => 'note-body', 'label' => $view->t('admin.messages.add_note'), 'placeholder' => $view->t('admin.messages.note_placeholder'), 'error' => $errors['body'] ?? null, 'rows' => 3, 'maxlength' => 2000]) ?>

        <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.messages.add_note'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
      </form>
<?php endif; ?>
    </div>
  </div>
</div>
