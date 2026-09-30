<?php
/**
 * Dashboard (approved screen admin-dashboard.html) with live data: requests this week, unread messages, the Google
 * rating and reviews on the website (from the Google reviews module), recent requests, quick controls and the 2FA
 * warning.
 *
 * @var \Gate\Core\View $view
 * @var bool $twoFactorOn
 * @var string $siteName
 * @var string $adminPath
 * @var array{name: string}|null $user
 * @var array{requests: int, requests_delta: string, unread: int, unread_delta: string, rating: string, rating_delta: string, reviews_visible: string, reviews_delta: string} $kpi
 * @var list<array<string, mixed>> $recent
 * @var list<array{key: string, name: string, description: string, checked: bool}> $quick
 */
$rows = [];
foreach ($recent as $a) {
    $rows[] = [
        'when' => (string) $a['when'],
        'who' => (string) $a['who'],
        'car' => (string) $a['car'],
        'box' => $view->component('status-pill', ['label' => (string) $a['box']]),
        'status' => $view->component('status-pill', ['label' => (string) $a['label'], 'tone' => (string) $a['tone']]),
    ];
}
?>
<div class="kpis">
  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_requests'), 'icon' => 'fa-regular fa-calendar', 'value' => (string) $kpi['requests'], 'delta' => $kpi['requests_delta'], 'tone' => 'up']) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_unread'), 'icon' => 'fa-regular fa-envelope', 'value' => (string) $kpi['unread'], 'delta' => $kpi['unread_delta'], 'tone' => $kpi['unread'] > 0 ? 'late' : 'neutral']) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_rating'), 'icon' => 'fa-solid fa-star', 'value' => $kpi['rating'], 'delta' => $kpi['rating_delta']]) ?>

  <?= $view->component('kpi-card', ['label' => $view->t('admin.dashboard.kpi_reviews'), 'icon' => 'fa-regular fa-eye', 'value' => $kpi['reviews_visible'], 'delta' => $kpi['reviews_delta']]) ?>

</div>
<div class="flex gap-20 agrow">
  <div class="acard agrow col">
    <div class="card-head"><h2 class="h3"><?= e($view->t('admin.dashboard.recent_title')) ?></h2><a class="link-sm" href="<?= e_url($adminPath . '/appointments') ?>"><?= e($view->t('admin.dashboard.all_requests')) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'size' => '14', 'class' => 'ic-ne']) ?></a></div>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.dashboard.recent_title'),
        'columns' => [
            ['key' => 'when', 'label' => $view->t('admin.appointments.received'), 'class' => 't-muted'],
            ['key' => 'who', 'label' => $view->t('admin.appointments.customer'), 'class' => 't-strong'],
            ['key' => 'car', 'label' => $view->t('admin.appointments.vehicle')],
            ['key' => 'box', 'label' => $view->t('site.form.type')],
            ['key' => 'status', 'label' => $view->t('admin.appointments.status')],
        ],
        'rows' => $rows,
        'empty' => $view->component('empty-state', ['title' => $view->t('admin.appointments.empty_title'), 'text' => $view->t('admin.appointments.empty_text'), 'icon' => 'fa-regular fa-calendar']),
    ]) ?>

  </div>
  <div class="side-380">
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
