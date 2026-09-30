<?php
/**
 * Admin sidebar: 80px icon rail that expands to 268px on hover/focus, drawer below 1024px (ui.js).
 * Items, sections, badges and role permissions come from config/admin-menu.php through AdminMenu::build().
 * Parameters: Components::SPECS['admin-sidebar'].
 *
 * @var \Gate\Core\View $view
 * @var array{menu: list<array{section: string, items: list<array{key: string, label: string, icon: string, href: string, badge: ?int}>}>, active: string, brandMark: string, brandName: string, logoutAction: ?string, open: bool, id: string} $p
 */

use Gate\Core\Props;

?>
<aside class="<?= e_attr(Props::classes('sb', $p['open'] ? 'open' : null)) ?>" id="<?= e_attr($p['id']) ?>" aria-label="<?= e_attr($view->t('admin.nav.label')) ?>">
  <div class="sb__brand"><span class="sb__mark"><?= e($p['brandMark']) ?></span><span class="tx sb__name"><span class="sb__title"><?= e(mb_strtoupper($p['brandName'])) ?></span><span class="sb__sub"><?= e($view->t('admin.nav.panel')) ?></span></span></div>
<?php foreach ($p['menu'] as $section): ?>
  <div class="sec"><span class="tx"><?= e($view->t('admin.nav.section_' . $section['section'])) ?></span></div>
<?php foreach ($section['items'] as $item): ?>
<?php $on = $item['key'] === $p['active']; ?>
  <a class="<?= $on ? 'it on' : 'it' ?>" href="<?= e_url($item['href']) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= $view->component('icon', ['icon' => $item['icon'], 'size' => '20']) ?><span class="tx"><?= e($view->t($item['label'])) ?></span><?php if ($item['badge'] !== null): ?><span class="badge tx"><?= (int) $item['badge'] ?></span><?php endif; ?></a>
<?php endforeach; ?>
<?php endforeach; ?>
<?php if ($p['logoutAction'] !== null): ?>
  <form class="it--end" method="post" action="<?= e_url($p['logoutAction']) ?>"><?= $view->csrfField() ?><button class="it" type="submit"><?= $view->component('icon', ['icon' => 'fa-solid fa-right-from-bracket', 'size' => '20']) ?><span class="tx"><?= e($view->t('admin.nav.logout')) ?></span></button></form>
<?php endif; ?>
</aside>
<div class="sb-scrim" data-sb-scrim></div>
