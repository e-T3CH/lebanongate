<?php
/**
 * Icon: Font Awesome glyph centred in a fixed box of the drawn icon size (decorative, aria-hidden).
 * Parameters: Components::SPECS['icon'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{icon: string, size: string, class: string} $p
 */

use BMMatic\Core\Props;

?>
<i class="<?= e_attr(Props::classes('ic ic-' . $p['size'], $p['class'], $p['icon'])) ?>" aria-hidden="true"></i>
