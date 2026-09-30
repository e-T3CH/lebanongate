<?php
/**
 * Legal pages (privacy policy, cookie policy, terms): heading and rich text with the company details filled in.
 *
 * @var \BMMatic\Core\View $view
 * @var array<string, mixed> $page
 * @var string $body rich text with placeholders already replaced
 */
?>
<?= $view->component('page-hero', ['label' => (string) $page['label'], 'title' => (string) $page['title'], 'lead' => (string) $page['intro'], 'breadcrumbs' => $page['breadcrumbs']]) ?>

<section class="sec sec--last" aria-label="<?= e_attr((string) $page['title']) ?>">
  <?= $view->component('prose', ['html' => $body]) ?>

</section>
