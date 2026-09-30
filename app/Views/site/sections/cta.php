<?php
/**
 * Home: call-to-action band (texts from the section when set, else the defaults).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $s section: title, intro, cta
 */
$cta = $s['cta'];
if ($s['title'] !== '') {
    $cta['title'] = $s['title'];
}
if ($s['intro'] !== '') {
    $cta['text'] = $s['intro'];
}
?>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
