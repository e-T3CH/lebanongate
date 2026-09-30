<?php
/**
 * Card: public (.card, dark, hover lift) or admin (.acard, white). The content slot takes Html or escaped text.
 * Parameters: Components::SPECS['card'].
 *
 * @var \BMMatic\Core\View $view
 * @var array{content: \BMMatic\Core\Html|string, variant: string, tag: string, href: ?string, class: string, id: ?string, attrs: array<string, string|int|bool|null>} $p
 */

use BMMatic\Core\Html;
use BMMatic\Core\Props;

$tag = $p['href'] !== null ? 'a' : $p['tag'];
$class = Props::classes($p['variant'] === 'admin' ? 'acard' : 'card', $p['class']);
?>
<<?= $tag ?> class="<?= e_attr($class) ?>"<?= $p['id'] !== null ? ' id="' . e_attr($p['id']) . '"' : '' ?><?= $p['href'] !== null ? ' href="' . e_url($p['href']) . '"' : '' ?><?= Props::attrs($p['attrs']) ?>><?= Html::of($p['content']) ?></<?= $tag ?>>
