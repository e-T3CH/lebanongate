<?php
/**
 * Admin layout: sidebar + top bar components, flash toast, confirm modal.
 *
 * @var \Gate\Core\View $view
 * @var string $content
 * @var string $pageTitle
 * @var string $pageSubtitle
 * @var string $active
 * @var string $adminPath
 * @var list<array{section: string, items: list<array{key: string, label: string, icon: string, href: string, badge: ?int}>}> $menu
 * @var string $siteName
 * @var array{name: string, role: string}|null $user
 * @var array{type: string, message: string}|null $toast
 */
?>
<?= $view->partial('head', ['title' => $pageTitle . ' — ' . $siteName . ' admin', 'bundle' => 'admin', 'noindex' => true]) ?>
<body class="admin">
<?= $view->component('admin-sidebar', ['menu' => $menu, 'active' => $active, 'brandName' => $siteName, 'logoutAction' => $adminPath . '/logout']) ?>

<div class="aw">
<?= $view->component('admin-topbar', ['title' => $pageTitle, 'subtitle' => $pageSubtitle, 'userName' => $user['name'] ?? null, 'userRole' => $user !== null ? $view->t('admin.roles.' . $user['role']) : null]) ?>

<main class="amain">
<?= $content ?>
</main>
</div>
<?php if (!empty($toast)): ?>
<?= $view->component('toast', ['type' => $toast['type'], 'message' => $toast['message']]) ?>

<?php endif; ?>
<?= $view->component('confirm-modal') ?>

</body>
</html>
