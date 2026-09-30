<?php
/**
 * Empty state: icon tile, title, explanation and an optional action (usually a button component).
 * Parameters: Components::SPECS['empty-state'].
 *
 * @var \Gate\Core\View $view
 * @var array{title: string, text: ?string, icon: string, action: \Gate\Core\Html|string|null, variant: string, class: string} $p
 */

use Gate\Core\Html;
use Gate\Core\Props;

?>
<div class="<?= e_attr(Props::classes('empty', $p['variant'] === 'public' ? 'empty--public' : null, $p['class'])) ?>"><span class="empty__icon"><?= $view->component('icon', ['icon' => $p['icon'], 'size' => '22']) ?></span><p class="empty__title"><?= e($p['title']) ?></p><?php if ($p['text'] !== null): ?><p class="empty__text"><?= e($p['text']) ?></p><?php endif; ?><?php if ($p['action'] !== null): ?><div class="empty__action"><?= Html::of($p['action']) ?></div><?php endif; ?></div>
