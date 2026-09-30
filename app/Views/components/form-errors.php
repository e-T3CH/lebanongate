<?php
/**
 * Form error summary shown above a form after a failed submit (role=alert).
 * Admin: warning card; public: dark alert box. With a title the messages are listed below it.
 * Parameters: Components::SPECS['form-errors'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{messages: list<string>, title: ?string, variant: string, icon: string, class: string} $p
 */

use BMMatic\Core\Props;

$messages = array_values(array_filter($p['messages'], 'is_string'));
if ($messages === [] && $p['title'] === null) {
    return;
}
$icon = $view->component('icon', ['icon' => $p['icon'], 'size' => '18']);
?>
<?php if ($p['variant'] === 'admin'): ?>
<div class="<?= e_attr(Props::classes('acard warn', $p['class'])) ?>" role="alert"><span class="tile"><?= $icon ?></span><span class="warn__col"><?php if ($p['title'] !== null): ?><span class="warn__title"><?= e($p['title']) ?></span><?php foreach ($messages as $m): ?><span class="warn__text"><?= e($m) ?></span><?php endforeach; ?><?php else: ?><?php foreach ($messages as $m): ?><span class="warn__title"><?= e($m) ?></span><?php endforeach; ?><?php endif; ?></span></div>
<?php else: ?>
<div class="<?= e_attr(Props::classes('form-alert', $p['class'])) ?>" role="alert"><span class="form-alert__icon"><?= $icon ?></span><span class="form-alert__col"><?php if ($p['title'] !== null): ?><span class="form-alert__title"><?= e($p['title']) ?></span><?php endif; ?><?php foreach ($messages as $m): ?><span class="form-alert__text"><?= e($m) ?></span><?php endforeach; ?></span></div>
<?php endif; ?>
