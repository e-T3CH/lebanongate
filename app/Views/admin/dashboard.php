<?php
/**
 * Dashboard: messages this week, unread messages, projects and newsletter subscribers; the newest messages, shortcuts
 * to add content, the quick controls and the 2FA warning.
 *
 * @var \Gate\Core\View $view
 * @var bool $twoFactorOn
 * @var string $siteName
 * @var string $adminPath
 * @var array{name: string}|null $user
 * @var array{messages: int, unread: int, projects: int, news: int, subscribers: int, pending: int} $kpi
 * @var list<array{id: int, when: string, who: string, subject: string, unread: bool}> $recent
 * @var list<array{key: string, name: string, description: string, checked: bool}> $quick
 * @var list<array{label: string, action: string, icon: string}> $shortcuts
 * @var bool $canMessages
 */
use Gate\Core\Html;

$rows = [];
foreach ($recent as $m) {
    $rows[] = [
        'when' => $m['when'],
        'who' => $canMessages ? Html::trusted('<a class="link-sm" href="' . e_url($adminPath . '/messages/' . $m['id']) . '">' . e($m['who']) . '</a>') : $m['who'],
        'subject' => $m['subject'],
        'status' => $view->component('status-pill', ['label' => $view->t($m['unread'] ? 'admin.messages.unread' : 'admin.messages.read'), 'tone' => $m['unread'] ? 'new' : 'neutral', 'dot' => $m['unread']]),
    ];
}
?>
<div class="kpis">
  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_messages'), 'icon' => 'fa-regular fa-envelope', 'value' => (string) $kpi['messages'], 'delta' => $view->t('admin.dashboard.kpi_messages_delta'), 'tone' => 'up']) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_unread'), 'icon' => 'fa-solid fa-inbox', 'value' => (string) $kpi['unread'], 'delta' => $view->t($kpi['unread'] > 0 ? 'admin.dashboard.kpi_unread_delta' : 'admin.dashboard.kpi_unread_none'), 'tone' => $kpi['unread'] > 0 ? 'late' : 'neutral']) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_projects'), 'icon' => 'fa-solid fa-diagram-project', 'value' => (string) $kpi['projects'], 'delta' => $view->t('admin.dashboard.kpi_news', ['count' => $kpi['news']])]) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_subscribers'), 'icon' => 'fa-solid fa-at', 'value' => (string) $kpi['subscribers'], 'delta' => $view->t('admin.dashboard.kpi_pending', ['count' => $kpi['pending']])]) ?>

</div>
<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.dashboard.recent_title')) ?></h2>
<?php if ($canMessages): ?>
      <a class="link-sm" href="<?= e_url($adminPath . '/messages') ?>"><?= e($view->t('admin.dashboard.all_messages')) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'size' => '14', 'class' => 'ic-ne']) ?></a>
<?php endif; ?>
    </div>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.dashboard.recent_title'),
        'columns' => [
            ['key' => 'when', 'label' => $view->t('admin.messages.received'), 'class' => 't-muted t-date'],
            ['key' => 'who', 'label' => $view->t('admin.messages.sender'), 'class' => 't-strong'],
            ['key' => 'subject', 'label' => $view->t('site.form.subject')],
            ['key' => 'status', 'label' => $view->t('admin.messages.status')],
        ],
        'rows' => $rows,
        'empty' => $view->component('empty-state', ['title' => $view->t('admin.messages.empty_title'), 'text' => $view->t('admin.messages.empty_text'), 'icon' => 'fa-regular fa-envelope']),
    ]) ?>

  </div>
  <div class="side-380">
<?php if ($shortcuts !== []): ?>
    <div class="acard acard--list">
      <h2 class="h3 acard__title"><?= e($view->t('admin.dashboard.shortcuts')) ?></h2>
      <div class="shortcuts">
<?php foreach ($shortcuts as $shortcut): ?>
        <form method="post" action="<?= e_url($shortcut['action']) ?>"><?= $view->csrfField() ?><?= $view->component('button', ['label' => $shortcut['label'], 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => $shortcut['icon'], 'iconPosition' => 'start', 'class' => 'shortcut']) ?></form>
<?php endforeach; ?>
      </div>
    </div>
<?php endif; ?>
<?php if ($quick !== []): ?>
    <div class="acard acard--list">
      <h2 class="h3 acard__title"><?= e($view->t('admin.dashboard.quick_title')) ?></h2>
<?php foreach ($quick as $item): ?>
      <form class="quick" method="post" action="<?= e_url($adminPath . '/quick-toggle') ?>" data-ajax>
        <?= $view->csrfField() ?><input type="hidden" name="key" value="<?= e_attr($item['key']) ?>">
        <?= $view->component('setting-row', [
            'name' => $item['name'],
            'description' => $item['description'],
            'control' => $view->component('toggle', ['label' => $item['name'], 'name' => 'value', 'autosave' => true, 'checked' => $item['checked'], 'toast' => $view->t('admin.toast.saved')]),
        ]) ?>

        <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-secondary', 'type' => 'submit', 'class' => 'quick__save']) ?>

      </form>
<?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if (!$twoFactorOn): ?>
    <?= $view->component('notice', ['title' => $view->t('admin.dashboard.two_factor_off_title'), 'text' => $view->t('admin.dashboard.two_factor_off_text'), 'linkHref' => $adminPath . '/security', 'linkLabel' => $view->t('admin.dashboard.two_factor_off_link') . ' →']) ?>

<?php endif; ?>
  </div>
</div>
