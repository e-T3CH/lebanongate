<?php
/**
 * Field error message, referenced from the field with aria-describedby.
 * Parameters: Components::SPECS['form-error'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{message: string, id: ?string, class: string} $p
 */

use BMMatic\Core\Props;

?>
<span class="<?= e_attr(Props::classes('form-error', $p['class'])) ?>"<?= $p['id'] !== null ? ' id="' . e_attr($p['id']) . '"' : '' ?>><?= e($p['message']) ?></span>
