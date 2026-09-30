<?php
/**
 * Contact: the contact section with the page title as H1 (details, map tile, appointment form).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var array<string, mixed> $home
 * @var array<string, mixed> $contactSection
 */
$section = ['number' => null, 'label' => (string) $page['label'], 'title' => (string) $page['title'], 'highlight' => (string) $page['highlight'], 'intro' => (string) $page['intro']] + $contactSection;
$home['contact'] = ['headingLevel' => 'h1'] + (is_array($home['contact'] ?? null) ? $home['contact'] : []);
?>
<?= $view->render('site/sections/contact', ['section' => $section, 'home' => $home]) ?>
