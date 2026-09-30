<?php
/**
 * Home section "contact" (also the contact page): contact panel with details and map tile, appointment form.
 *
 * @var \BMMatic\Core\View $view
 * @var array{number: ?string, label: string, title: string, highlight?: string, intro: string, extra: array<string, mixed>} $section
 * @var array<string, mixed> $home
 */
$extra = $section['extra'];
$str = static fn (string $k): string => is_string($extra[$k] ?? null) ? $extra[$k] : '';
/** @var array{items: list<array{icon: string, text: string, href?: ?string}>, mapHref: ?string, form: array<string, mixed>, headingLevel?: string} $contact */
$contact = $home['contact'];
$title = $section['title'] . (isset($section['highlight']) && $section['highlight'] !== '' ? ' ' . $section['highlight'] : '');
?>
<section class="contact page__grow" id="contact">
  <?= $view->component('contact-panel', ['number' => $section['number'], 'label' => $section['label'], 'title' => $title, 'intro' => $section['intro'] !== '' ? $section['intro'] : null, 'items' => $contact['items'], 'mapLabel' => $str('map'), 'mapHref' => $contact['mapHref'], 'headingLevel' => $contact['headingLevel'] ?? 'h2']) ?>

  <?= $view->component('appointment-form', ['title' => $str('form_title'), 'submitLabel' => $str('submit')] + $contact['form']) ?>

</section>
