<?php
/**
 * Toast (bottom right, auto-dismiss after 4s): success, error, info, warning. Rendered server-side for a flash
 * message after a redirect; BM.toast(type, message) builds the same markup for AJAX results.
 * Errors use role=alert, the others role=status.
 * Parameters: Components::SPECS['toast'].
 *
 * @var \Gate\Core\View $view
 * @var array{message: string, type: string, class: string} $p
 */

use Gate\Core\Props;

$icons = ['success' => 'fa-solid fa-check', 'error' => 'fa-solid fa-xmark', 'info' => 'fa-solid fa-info', 'warning' => 'fa-solid fa-exclamation'];
$alert = $p['type'] === 'error';
?>
<div class="<?= e_attr(Props::classes('toast toast--' . $p['type'], $p['class'])) ?>" role="<?= $alert ? 'alert' : 'status' ?>" aria-live="<?= $alert ? 'assertive' : 'polite' ?>"><span class="toast__icon toast__icon--<?= e_attr($p['type']) ?>"><?= $view->component('icon', ['icon' => $icons[$p['type']], 'size' => '14']) ?></span><?= e($p['message']) ?></div>
