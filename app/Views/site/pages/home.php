<?php
/**
 * Home page: the enabled sections in their configured order (SectionOrder). Top bar, header and footer are part of the
 * layout; the rest are rendered from app/Views/site/sections.
 *
 * @var \Gate\Core\View $view
 * @var list<array<string, mixed>> $sections SectionOrder::visible() output
 * @var array<string, mixed> $home
 */
$html = '';
foreach ($sections as $section) {
    $type = is_string($section['type'] ?? null) ? $section['type'] : '';
    if (in_array($type, ['topbar', 'header', 'footer'], true) || preg_match('/^[a-z]+$/', $type) !== 1) {
        continue;
    }
    $html .= $view->render('site/sections/' . $type, ['section' => $section, 'home' => $home]) . "\n";
}
echo $html;
