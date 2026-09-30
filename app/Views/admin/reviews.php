<?php
/**
 * Content → Google reviews (approved screen admin-reviews.html) with live data: the connection and its limits,
 * what appears on the website, and the imported reviews with filters, per-review visibility and bulk actions.
 *
 * @var \Gate\Core\View $view
 * @var list<array<string, mixed>> $rows
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, pages: int} $result
 * @var array{all: int, visible: int, hidden: int, deleted: int} $counts
 * @var array<string, mixed> $filters
 * @var string $query
 * @var list<string> $languages
 * @var array<string, mixed> $connection
 * @var array<string, mixed> $values
 * @var array<string, bool> $display
 * @var array{value: string, count: string} $rating
 * @var mixed $import
 * @var array<string, string> $errors
 * @var bool $canManage
 * @var string $adminPath
 */

use Gate\Core\Html;

$str = static fn (string $key): string => is_scalar($values[$key] ?? null) ? (string) $values[$key] : '';
$conn = static fn (string $key): string => is_scalar($connection[$key] ?? null) ? (string) $connection[$key] : '';
$tab = is_string($filters['tab'] ?? null) ? $filters['tab'] : 'all';
$filterQuery = static function (array $extra) use ($filters, $adminPath): string {
    $merged = array_filter($extra + $filters, static fn ($v): bool => $v !== '' && $v !== null);
    return $adminPath . '/reviews' . ($merged === [] ? '' : '?' . http_build_query($merged));
};
$tableRows = [];
foreach ($rows as $row) {
    $id = (int) $row['id'];
    $name = (string) $row['name'];
    $text = (string) $row['text'];
    $reply = (string) $row['reply'];
    $tableRows[] = [
        'reviewer' => Html::trusted('<span class="person"><span class="person__avatar" aria-hidden="true">' . e((string) $row['initial']) . '</span><span class="person__col"><span class="person__name">' . e($name) . '</span><span class="person__date">' . e((string) $row['date']) . ((bool) $row['deleted'] ? ' · ' . e($view->t('admin.reviews.removed_at_google')) : '') . '</span></span></span>'),
        'rating' => $view->component('star-rating', ['rating' => (float) $row['rating'], 'size' => '14', 'variant' => 'admin']),
        'review' => Html::trusted(e(mb_strimwidth(trim((string) preg_replace('/\s+/u', ' ', $text)), 0, 140, '…'))
            . ($reply !== '' ? '<span class="review-reply">' . e($view->t('admin.reviews.owner_reply')) . ': ' . e(mb_strimwidth(trim((string) preg_replace('/\s+/u', ' ', $reply)), 0, 90, '…')) . '</span>' : '')),
        'lang' => (string) $row['language'] === '' ? '—' : $view->component('status-pill', ['label' => (string) $row['language']]),
        'visible' => Html::trusted('<span class="visibility" data-visibility>'
            . '<form method="post" action="' . e_url($adminPath . '/reviews/' . $id . '/visibility') . '" data-ajax>' . $view->csrfField()
            . $view->component('toggle', [
                'name' => 'value',
                'label' => $view->t('admin.reviews.show_review', ['name' => $name]),
                'checked' => (bool) $row['visible'],
                'autosave' => true,
                'toast' => $view->t('admin.reviews.visibility_saved'),
                'disabled' => !$canManage,
            ])
            . '<button class="abtn abtn-s btn-row quick__save" type="submit">' . e($view->t('admin.actions.save')) . '</button>'
            . '</form>'
            . '<span class="visibility__text' . ((bool) $row['visible'] ? ' is-visible' : '') . '" data-visibility-text>' . e($view->t((bool) $row['visible'] ? 'ui.review.visible' : 'ui.review.hidden')) . '</span></span>'),
    ];
}
?>
<div class="between">
  <span class="muted"><?= e($view->t('admin.reviews.intro')) ?></span>
<?php if ($canManage): ?>
  <div class="flex gap-10">
    <?= $view->component('button', ['label' => $view->t('admin.reviews.import_file'), 'variant' => 'admin-secondary', 'icon' => 'fa-solid fa-arrow-up-from-bracket', 'iconPosition' => 'start', 'href' => '#import']) ?>

    <form method="post" action="<?= e_url($adminPath . '/reviews/sync') ?>"><?= $view->csrfField() ?><?= $view->component('button', ['label' => $view->t('admin.reviews.sync_now'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-rotate-right', 'iconPosition' => 'start']) ?></form>
  </div>
<?php endif; ?>
</div>
<div class="two">
  <form class="acard connection" method="post" action="<?= e_url($adminPath . '/reviews/connection') ?>" novalidate>
    <?= $view->csrfField() ?>
    <div class="between">
      <h2 class="h3"><?= e($view->t('admin.reviews.connection')) ?></h2>
<?php if ($conn('last_error') !== ''): ?>
      <?= $view->component('status-pill', ['label' => $view->t('admin.reviews.state_error'), 'tone' => 'danger', 'dot' => true]) ?>

<?php elseif (($connection['configured'] ?? false) === true): ?>
      <?= $view->component('status-pill', ['label' => $conn('last_sync') === '' ? $view->t('admin.reviews.state_ready') : $view->t('admin.reviews.state_connected', ['date' => $conn('last_sync')]), 'tone' => 'confirmed', 'dot' => true]) ?>

<?php else: ?>
      <?= $view->component('status-pill', ['label' => $view->t('admin.reviews.state_not_configured'), 'tone' => 'diagnosis']) ?>

<?php endif; ?>
    </div>
    <div class="fields-2">
      <?= $view->component('select', ['id' => 'r-src', 'name' => 'provider', 'label' => $view->t('admin.reviews.source'), 'value' => $str('provider'), 'options' => [
          ['value' => 'business_profile', 'label' => $view->t('admin.reviews.source_business_profile')],
          ['value' => 'places', 'label' => $view->t('admin.reviews.source_places')],
          ['value' => 'manual', 'label' => $view->t('admin.reviews.source_manual')],
      ], 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['id' => 'r-id', 'name' => 'location_id', 'label' => $view->t('admin.reviews.location_id'), 'value' => $str('location_id'), 'error' => $errors['location_id'] ?? null, 'disabled' => !$canManage]) ?>

      <?= $view->component('select', ['id' => 'r-sync', 'name' => 'sync_interval_hours', 'label' => $view->t('admin.reviews.auto_sync'), 'value' => $str('sync_interval_hours'), 'options' => [
          ['value' => '6', 'label' => $view->t('admin.reviews.every_6')],
          ['value' => '12', 'label' => $view->t('admin.reviews.every_12')],
          ['value' => '24', 'label' => $view->t('admin.reviews.every_24')],
          ['value' => '0', 'label' => $view->t('admin.reviews.manual_only')],
      ], 'disabled' => !$canManage]) ?>

      <?= $view->component('select', ['id' => 'r-new', 'name' => 'new_visibility', 'label' => $view->t('admin.reviews.new_reviews'), 'value' => $str('new_visibility'), 'options' => [
          ['value' => 'hidden', 'label' => $view->t('admin.reviews.hidden_until_approved')],
          ['value' => 'visible', 'label' => $view->t('admin.reviews.visible_directly')],
      ], 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['name' => 'place_id', 'label' => $view->t('admin.reviews.place_id'), 'value' => $str('place_id'), 'error' => $errors['place_id'] ?? null, 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['name' => 'api_key', 'type' => 'password', 'label' => $view->t('admin.reviews.api_key'), 'hint' => $view->t(($values['has_api_key'] ?? false) ? 'admin.email.password_kept' : 'admin.email.password_empty'), 'autocomplete' => 'off', 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['name' => 'client_id', 'label' => $view->t('admin.reviews.client_id'), 'value' => $str('client_id'), 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['name' => 'client_secret', 'type' => 'password', 'label' => $view->t('admin.reviews.client_secret'), 'hint' => $view->t(($values['has_client_secret'] ?? false) ? 'admin.email.password_kept' : 'admin.email.password_empty'), 'autocomplete' => 'off', 'disabled' => !$canManage]) ?>

      <?= $view->component('input', ['name' => 'reviews_url', 'label' => $view->t('admin.reviews.google_url'), 'value' => $str('reviews_url'), 'error' => $errors['reviews_url'] ?? null, 'hint' => $view->t('admin.reviews.google_url_hint'), 'disabled' => !$canManage]) ?>

      <?= $view->component('select', ['name' => 'display_order', 'id' => 'r-order', 'label' => $view->t('admin.reviews.display_order'), 'value' => $str('display_order'), 'options' => [
          ['value' => 'newest', 'label' => $view->t('admin.reviews.order_newest')],
          ['value' => 'oldest', 'label' => $view->t('admin.reviews.order_oldest')],
          ['value' => 'highest', 'label' => $view->t('admin.reviews.order_highest')],
      ], 'disabled' => !$canManage]) ?>

    </div>
    <ul class="conn-facts">
      <li><?= e($view->t('admin.reviews.limits')) ?>: <?= e($conn('limit_note')) ?></li>
      <li><?= e($view->t('admin.reviews.last_sync')) ?>: <?= e($conn('last_sync') === '' ? $view->t('admin.common.never') : $conn('last_sync')) ?><?= $conn('last_result') !== '' ? ' · ' . e($conn('last_result')) : '' ?></li>
      <li><?= e($view->t('admin.reviews.next_sync')) ?>: <?= e($conn('next_sync') === '' ? $view->t('admin.reviews.manual_only') : $conn('next_sync')) ?></li>
      <li><?= e($view->t('admin.reviews.rating_from_google')) ?>: <?= e($rating['value'] === '' ? '—' : $rating['value']) ?> · <?= e($rating['count']) ?></li>
    </ul>
<?php if ($conn('last_error') !== ''): ?>
    <?= $view->component('notice', ['title' => $view->t('admin.reviews.last_error'), 'text' => $conn('last_error'), 'role' => 'alert']) ?>

<?php endif; ?>
<?php if ($str('provider') === 'business_profile' && ($connection['oauth_ready'] ?? false) === true): ?>
    <?= $view->component('notice', [
        'title' => $view->t(($connection['connected'] ?? false) ? 'admin.reviews.oauth_connected' : 'admin.reviews.oauth_needed'),
        'text' => $view->t('admin.reviews.oauth_hint'),
        'linkHref' => $adminPath . '/reviews/connect',
        'linkLabel' => $view->t(($connection['connected'] ?? false) ? 'admin.reviews.reconnect' : 'admin.reviews.connect'),
    ]) ?>

<?php endif; ?>
<?php if ($conn('test_endpoint') !== ''): ?>
    <?= $view->component('notice', ['title' => $view->t('admin.reviews.test_endpoint'), 'text' => $conn('test_endpoint'), 'role' => 'alert']) ?>

<?php endif; ?>
<?php if ($canManage): ?>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
<?php endif; ?>
  </form>
  <div class="acard acard--list">
    <h2 class="h3 acard__title--4"><?= e($view->t('admin.reviews.display_title')) ?></h2>
<?php foreach (['section_enabled' => 'admin.reviews.display_section', 'show_rating_badge' => 'admin.reviews.display_badge', 'show_photos' => 'admin.reviews.display_photos', 'link_to_google' => 'admin.reviews.display_link'] as $key => $label): ?>
    <form class="quick" method="post" action="<?= e_url($adminPath . '/reviews/display') ?>" data-ajax>
      <?= $view->csrfField() ?><input type="hidden" name="key" value="<?= e_attr($key) ?>">
      <?= $view->component('setting-row', ['name' => $view->t($label), 'control' => $view->component('toggle', ['name' => 'value', 'label' => $view->t($label), 'checked' => $display[$key], 'autosave' => true, 'toast' => $view->t('admin.toast.saved'), 'disabled' => !$canManage])]) ?>

      <?= $view->component('button', ['label' => $view->t('admin.actions.save'), 'variant' => 'admin-secondary', 'type' => 'submit', 'class' => 'quick__save']) ?>

    </form>
<?php endforeach; ?>
  </div>
</div>
<div class="acard agrow col">
  <div class="table-head">
    <?= $view->component('tabs', ['variant' => 'reviews', 'label' => $view->t('admin.reviews.filter_label'), 'items' => [
        ['label' => $view->t('admin.common.all'), 'count' => $counts['all'], 'href' => $filterQuery(['tab' => null]), 'active' => $tab === 'all'],
        ['label' => $view->t('ui.review.visible'), 'count' => $counts['visible'], 'href' => $filterQuery(['tab' => 'visible']), 'active' => $tab === 'visible'],
        ['label' => $view->t('ui.review.hidden'), 'count' => $counts['hidden'], 'href' => $filterQuery(['tab' => 'hidden']), 'active' => $tab === 'hidden'],
    ]]) ?>

    <form class="filters" method="get" action="<?= e_url($adminPath . '/reviews') ?>">
<?php if ($tab !== 'all'): ?>
      <input type="hidden" name="tab" value="<?= e_attr($tab) ?>">
<?php endif; ?>
      <?= $view->component('select', ['name' => 'rating', 'ariaLabel' => $view->t('admin.reviews.filter_rating'), 'panelLabel' => $view->t('admin.reviews.rating'), 'value' => isset($filters['rating']) ? (string) $filters['rating'] : 'all', 'size' => 'sm', 'panel' => 'rating', 'check' => 'inline', 'class' => 'w-150', 'options' => [
          ['value' => 'all', 'label' => $view->t('admin.reviews.all_ratings')],
          ['value' => '5', 'label' => $view->t('admin.reviews.stars_5'), 'stars' => 5],
          ['value' => '4', 'label' => $view->t('admin.reviews.stars_4'), 'stars' => 4, 'suffix' => $view->t('admin.reviews.and_up')],
          ['value' => '3', 'label' => $view->t('admin.reviews.stars_3'), 'stars' => 3, 'suffix' => $view->t('admin.reviews.and_up')],
      ]]) ?>

      <?= $view->component('select', ['name' => 'language', 'ariaLabel' => $view->t('admin.reviews.filter_language'), 'value' => is_string($filters['language'] ?? null) ? $filters['language'] : '', 'size' => 'sm', 'class' => 'w-130', 'options' => array_merge(
          [['value' => '', 'label' => $view->t('admin.reviews.all_languages')]],
          array_map(static fn (string $l): array => ['value' => $l, 'label' => strtoupper($l)], $languages)
      )]) ?>

      <?= $view->component('input', ['name' => 'q', 'type' => 'search', 'ariaLabel' => $view->t('admin.reviews.search'), 'value' => is_string($filters['q'] ?? null) ? $filters['q'] : '', 'placeholder' => $view->t('admin.reviews.search_placeholder'), 'class' => 'w-200', 'inputClass' => 'in--13']) ?>

      <?= $view->component('button', ['label' => $view->t('admin.common.apply'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-magnifying-glass', 'iconPosition' => 'start', 'iconOnly' => true, 'class' => 'btn-row btn-icon']) ?>

    </form>
  </div>
<?php if ($canManage && $result['total'] > 0): ?>
  <div class="bulk">
    <span class="muted"><?= e($view->t('admin.reviews.bulk_hint', ['count' => $result['total']])) ?></span>
    <?= $view->component('row-actions', ['actions' => [
        ['action' => $adminPath . '/reviews/bulk', 'label' => $view->t('admin.reviews.bulk_show'), 'icon' => 'fa-regular fa-eye', 'fields' => ['visible' => '1'] + array_map('strval', $filters), 'confirm' => $view->t('admin.reviews.bulk_show_confirm', ['count' => $result['total']]), 'confirmTitle' => $view->t('admin.reviews.bulk_show')],
        ['action' => $adminPath . '/reviews/bulk', 'label' => $view->t('admin.reviews.bulk_hide'), 'icon' => 'fa-solid fa-xmark', 'fields' => ['visible' => '0'] + array_map('strval', $filters), 'confirm' => $view->t('admin.reviews.bulk_hide_confirm', ['count' => $result['total']]), 'confirmTitle' => $view->t('admin.reviews.bulk_hide')],
    ]]) ?>

  </div>
<?php endif; ?>
  <?= $view->component('data-table', [
      'caption' => $view->t('admin.reviews.title'),
      'columns' => [
          ['key' => 'reviewer', 'label' => $view->t('admin.reviews.reviewer')],
          ['key' => 'rating', 'label' => $view->t('admin.reviews.rating')],
          ['key' => 'review', 'label' => $view->t('admin.reviews.review'), 'class' => 't-review'],
          ['key' => 'lang', 'label' => $view->t('admin.reviews.lang')],
          ['key' => 'visible', 'label' => $view->t('admin.reviews.on_website')],
      ],
      'rows' => $tableRows,
      'empty' => $view->component('empty-state', ['title' => $view->t('admin.reviews.empty_title'), 'text' => $view->t('admin.reviews.empty_text'), 'icon' => 'fa-regular fa-star']),
  ]) ?>

<?php if ($result['pages'] > 1): ?>
  <?= $view->component('pagination', ['page' => $result['page'], 'pages' => $result['pages'], 'url' => $adminPath . '/reviews' . ($query === '' ? '?' : $query . '&') . 'page={page}']) ?>

<?php endif; ?>
</div>
<?php if ($canManage): ?>
<div class="acard panel-fields" id="import">
  <div class="card-intro card-intro--flush"><h2 class="h3"><?= e($view->t('admin.reviews.import_title')) ?></h2><span class="muted"><?= e($view->t('admin.reviews.import_desc')) ?></span></div>
<?php if (is_array($import)): ?>
  <form method="post" action="<?= e_url($adminPath . '/reviews/import/confirm') ?>" class="panel-fields">
    <?= $view->csrfField() ?>
    <span class="muted"><?= e($view->t('admin.reviews.import_preview', ['total' => (int) ($import['total'] ?? 0), 'duplicates' => (int) ($import['duplicates'] ?? 0)])) ?></span>
    <div class="fields-3">
<?php foreach (\Gate\Reviews\ManualImportProvider::FIELDS as $field): ?>
      <?= $view->component('select', ['name' => 'map_' . $field, 'id' => 'map-' . str_replace('_', '-', $field), 'label' => $view->t('admin.reviews.field_' . $field), 'value' => is_string($import['mapping'][$field] ?? null) ? $import['mapping'][$field] : '', 'options' => array_merge(
          [['value' => '', 'label' => $view->t('admin.reviews.column_none')]],
          array_map(static fn (string $c): array => ['value' => $c, 'label' => $c], is_array($import['columns'] ?? null) ? $import['columns'] : [])
      )]) ?>

<?php endforeach; ?>
    </div>
    <?= $view->component('data-table', [
        'caption' => $view->t('admin.reviews.import_preview_title'),
        'columns' => [
            ['key' => 'name', 'label' => $view->t('admin.reviews.reviewer'), 'class' => 't-strong'],
            ['key' => 'rating', 'label' => $view->t('admin.reviews.rating')],
            ['key' => 'date', 'label' => $view->t('admin.appointments.received'), 'class' => 't-muted'],
            ['key' => 'text', 'label' => $view->t('admin.reviews.review'), 'class' => 't-review'],
        ],
        'rows' => array_map(static fn (array $r): array => ['name' => (string) $r['name'], 'rating' => (string) $r['rating'], 'date' => (string) $r['date'], 'text' => (string) $r['text']], is_array($import['preview'] ?? null) ? $import['preview'] : []),
    ]) ?>

    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.reviews.import_confirm'), 'variant' => 'admin-primary', 'type' => 'submit', 'icon' => 'fa-solid fa-check', 'iconPosition' => 'start']) ?></div>
  </form>
<?php else: ?>
  <form method="post" action="<?= e_url($adminPath . '/reviews/import') ?>" enctype="multipart/form-data" class="panel-fields">
    <?= $view->csrfField() ?>
    <label class="file-field">
      <span class="file-field__label"><?= e($view->t('admin.reviews.import_file_label')) ?></span>
      <input type="file" name="file" accept=".json,.csv,application/json,text/csv" required>
    </label>
    <div class="actions"><?= $view->component('button', ['label' => $view->t('admin.reviews.import_preview_button'), 'variant' => 'admin-secondary', 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-up-from-bracket', 'iconPosition' => 'start']) ?></div>
  </form>
<?php endif; ?>
</div>
<?php endif; ?>
