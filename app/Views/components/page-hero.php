<?php
/**
 * Page heading for content pages: optional breadcrumbs, label, H1 with accent highlight, lead and actions.
 * Same type scale and spacing as the approved section headers.
 * Parameters: Components::SPECS['page-hero'].
 *
 * @var \Gate\Core\View $view
 * @var array{title: string, label: ?string, highlight: ?string, lead: ?string, breadcrumbs: list<array{label: string, href?: ?string}>, actions: \Gate\Core\Html|string|null, class: string} $p
 */

use Gate\Core\Html;
use Gate\Core\Props;

?>
<section class="<?= e_attr(Props::classes('page-hero', $p['class'])) ?>">
<?php if ($p['breadcrumbs'] !== []): ?>
  <?= $view->component('breadcrumbs', ['items' => $p['breadcrumbs']]) ?>

<?php endif; ?>
<?php if ($p['label'] !== null && $p['label'] !== ''): ?>
  <?= $view->component('section-label', ['text' => $p['label']]) ?>

<?php endif; ?>
  <h1><?= e($p['title']) ?><?= $p['highlight'] !== null && $p['highlight'] !== '' ? ' <em>' . e($p['highlight']) . '</em>' : '' ?></h1>
<?php if ($p['lead'] !== null && $p['lead'] !== ''): ?>
  <p class="page-hero__lead"><?= e($p['lead']) ?></p>
<?php endif; ?>
<?php if ($p['actions'] !== null): ?>
  <div class="page-hero__cta"><?= Html::of($p['actions']) ?></div>
<?php endif; ?>
</section>
