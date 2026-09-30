<?php
/**
 * Rich text block (page and service bodies), sanitised with HTML Purifier (RichText) before output.
 * Parameters: Components::SPECS['prose'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{html: string, class: string} $p
 */

use BMMatic\Core\Props;
use BMMatic\Site\RichText;

?>
<div class="<?= e_attr(Props::classes('prose', $p['class'])) ?>"><?= RichText::render($p['html']) ?></div>
