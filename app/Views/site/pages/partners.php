<?php
/**
 * Partners & donors: the text, then donors and partners as logo cards with a short description.
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var string $body
 * @var list<array{id: int, name: string, kind: string, url: string, logo: array<string, mixed>|null, description: string}> $donors
 * @var list<array{id: int, name: string, kind: string, url: string, logo: array<string, mixed>|null, description: string}> $partners
 * @var array<string, mixed> $cta
 */
$group = static function (string $title, array $items) use ($view): string {
    if ($items === []) {
        return '';
    }
    $html = '<h2 class="h2 reveal">' . e($title) . '</h2><ul class="partner-grid" data-stagger>';
    foreach ($items as $p) {
        $logo = is_array($p['logo']) ? '<img src="' . e_url($p['logo']['url']) . '" alt="" loading="lazy" decoding="async">' : svg_icon('hands', 'ic partner-card__ph');
        $name = $p['url'] !== '' ? '<a href="' . e_url($p['url']) . '" rel="noopener" target="_blank">' . e($p['name']) . '</a>' : e($p['name']);
        $html .= '<li class="partner-card reveal"><div class="partner-card__logo">' . $logo . '</div><h3 class="h4">' . $name . '</h3>'
            . ($p['description'] !== '' ? '<p>' . e($p['description']) . '</p>' : '') . '</li>';
    }
    return $html . '</ul>';
};
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section">
  <div class="wrap">
<?php if (trim(strip_tags($body)) !== ''): ?>
    <div class="prose narrow-block reveal"><?= $body /* RichText::render(): sanitised */ ?></div>
<?php endif; ?>
    <?= $group($view->t('site.partners.donors'), $donors) ?>
    <?= $group($view->t('site.partners.partners'), $partners) ?>
  </div>
</section>
<?= $view->render('site/parts/cta', ['cta' => $cta]) ?>
