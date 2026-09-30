<?php
/**
 * Admin top bar (a <header>, so the whole screen is inside a landmark): sidebar toggle (below 1024px), subtitle + page title, search, "View site", notifications, user.
 * Parameters: Components::SPECS['admin-topbar'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{title: string, subtitle: string, userName: ?string, userRole: ?string, viewSiteHref: ?string, search: bool, searchAction: ?string, notifications: ?int, userMenu: bool, sidebarId: string} $p
 */

$name = trim((string) $p['userName']);
$words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
$initials = count($words) > 1
    ? mb_substr($words[0], 0, 1) . mb_substr((string) end($words), 0, 1)
    : mb_substr($name, 0, 2);
$initials = mb_strtoupper($initials);
$searchInner = '<span class="search__icon">' . $view->component('icon', ['icon' => 'fa-solid fa-magnifying-glass']) . '</span><input class="search__input" type="search"'
    . ($p['searchAction'] !== null ? ' name="q"' : '') . ' aria-label="' . e_attr($view->t('ui.admin.search')) . '" placeholder="' . e_attr($view->t('ui.admin.search_placeholder')) . '">';
$userInner = '<span class="user__avatar" aria-hidden="true">' . e($initials) . '</span><span class="user__col"><span class="user__name">' . e($name) . '</span><span class="user__role">' . e((string) $p['userRole']) . '</span></span>';
?>
<header class="atop">
  <div class="atop__lead">
    <button class="atop__menu" type="button" aria-label="<?= e_attr($view->t('admin.nav.open')) ?>" aria-controls="<?= e_attr($p['sidebarId']) ?>" aria-expanded="false" data-sb-toggle><?= $view->component('icon', ['icon' => 'fa-solid fa-bars', 'size' => '20']) ?></button>
    <div class="atop__titles"><span class="atop__sub"><?= e($p['subtitle']) ?></span><h1 class="atop__title"><?= e($p['title']) ?></h1></div>
  </div>
  <div class="atop__tools">
<?php if ($p['search']): ?>
<?php if ($p['searchAction'] !== null): ?>
    <form class="ul search" role="search" method="get" action="<?= e_url($p['searchAction']) ?>"><?= $searchInner ?></form>
<?php else: ?>
    <div class="ul search" role="search"><?= $searchInner ?></div>
<?php endif; ?>
<?php endif; ?>
<?php if ($p['viewSiteHref'] !== null): ?>
    <a class="link-sm" href="<?= e_url($p['viewSiteHref']) ?>"><?= e($view->t('admin.top.view_site')) ?> <?= $view->component('icon', ['icon' => 'fa-solid fa-arrow-right', 'size' => '14', 'class' => 'ic-ne']) ?></a>
<?php endif; ?>
<?php if ($p['notifications'] !== null): ?>
    <button class="notif" type="button" aria-label="<?= e_attr($view->t($p['notifications'] > 0 ? 'ui.admin.new_notifications' : 'ui.admin.notifications')) ?>"><?= $view->component('icon', ['icon' => 'fa-regular fa-bell', 'size' => '18']) ?><?= $p['notifications'] > 0 ? '<span class="notif__dot"></span>' : '' ?></button>
<?php endif; ?>
<?php if ($p['userName'] !== null): ?>
<?php if ($p['userMenu']): ?>
    <button class="user" type="button" aria-haspopup="menu"><?= $userInner ?><?= $view->component('icon', ['icon' => 'fa-solid fa-chevron-down', 'size' => '14', 'class' => 'ic-chev']) ?></button>
<?php else: ?>
    <span class="user"><?= $userInner ?></span>
<?php endif; ?>
<?php endif; ?>
  </div>
</header>
