<?php
/**
 * Section label: small uppercase accent line above a heading ("02 — Services").
 * Parameters: Components::SPECS['section-label'].
 *
 * @var \Gate\Core\View $view
 * @var array{text: string, number: ?string, tag: string, class: string} $p
 */

use Gate\Core\Props;

$text = $p['number'] !== null ? $view->t('ui.section_label', ['number' => $p['number'], 'text' => $p['text']]) : $p['text'];
?>
<<?= $p['tag'] ?> class="<?= e_attr(Props::classes('lbl', $p['class'])) ?>"><?= e($text) ?></<?= $p['tag'] ?>>
