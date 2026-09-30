<?php
/**
 * Home page: the enabled sections in their order (admin: Pages → Home → sections).
 *
 * @var \Gate\Core\View $view
 * @var list<array<string, mixed>> $sections
 */
$known = ['hero', 'about', 'expertise', 'stats', 'projects', 'map', 'news', 'partners', 'cta'];
foreach ($sections as $section) {
    if (in_array($section['type'], $known, true)) {
        echo $view->render('site/sections/' . $section['type'], ['s' => $section]);
    }
}
