<?php
/**
 * Contact: details card (address with map link, phone, email, hours) and the contact form (server-side validation,
 * one message per field, CSRF, honeypot, time trap and rate limit).
 *
 * @var \Gate\Core\View $view
 * @var array<string, mixed> $page
 * @var list<array{label: string, href?: string}> $breadcrumbs
 * @var array<string, mixed>|null $hero
 * @var array{action: string, token: string, values: array<string, mixed>, errors: array<string, string>, subjects: list<string>, privacyHref: string|null} $form
 * @var array{address: string, mapHref: string|null, phone: string, phoneHref: string|null, email: string, emailHref: string|null, hours: string} $details
 */
$v = static fn (string $k): string => is_scalar($form['values'][$k] ?? null) ? (string) $form['values'][$k] : '';
$err = static fn (string $k): ?string => $form['errors'][$k] ?? null;
$field = static function (string $name, string $label, string $type, bool $required, string $autocomplete = '', bool $ltr = false) use ($v, $err, $view): string {
    $id = 'f-' . $name;
    $error = $err($name);
    return '<div class="field' . ($error !== null ? ' has-error' : '') . '"><label for="' . $id . '">' . e($label) . ($required ? ' <span class="req" aria-hidden="true">*</span>' : ' <span class="opt">' . e($view->t('site.form.optional')) . '</span>') . '</label>'
        . '<input id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . e_attr($v($name)) . '"' . ($required ? ' required' : '') . ($autocomplete !== '' ? ' autocomplete="' . $autocomplete . '"' : '') . ($ltr ? ' dir="ltr"' : '') . ($error !== null ? ' aria-invalid="true" aria-describedby="' . $id . '-error"' : '') . '>'
        . ($error !== null ? '<p class="field__error" id="' . $id . '-error">' . svg_icon('alert') . e($error) . '</p>' : '') . '</div>';
};
?>
<?= $view->render('site/parts/page-hero', ['title' => $page['title'], 'intro' => $page['intro'], 'label' => $page['label'], 'breadcrumbs' => $breadcrumbs, 'image' => $hero]) ?>
<section class="section">
  <div class="wrap contact">
    <aside class="contact__details reveal">
      <h2 class="h3"><?= e($view->t('site.contact.details')) ?></h2>
      <ul class="contact__list">
<?php if ($details['address'] !== ''): ?>
        <li><span class="contact__icon"><?= svg_icon('pin') ?></span><div><b><?= e($view->t('site.contact.address')) ?></b><?php if ($details['mapHref'] !== null): ?><a href="<?= e_url($details['mapHref']) ?>" target="_blank" rel="noopener"><?= e($details['address']) ?></a><?php else: ?><span><?= e($details['address']) ?></span><?php endif; ?></div></li>
<?php endif; ?>
<?php if ($details['phone'] !== ''): ?>
        <li><span class="contact__icon"><?= svg_icon('phone') ?></span><div><b><?= e($view->t('site.contact.phone')) ?></b><?php if ($details['phoneHref'] !== null): ?><a href="<?= e_url($details['phoneHref']) ?>" dir="ltr"><?= e($details['phone']) ?></a><?php else: ?><span dir="ltr"><?= e($details['phone']) ?></span><?php endif; ?></div></li>
<?php endif; ?>
<?php if ($details['email'] !== ''): ?>
        <li><span class="contact__icon"><?= svg_icon('mail') ?></span><div><b><?= e($view->t('site.contact.email')) ?></b><?php if ($details['emailHref'] !== null): ?><a href="<?= e_url($details['emailHref']) ?>" dir="ltr"><?= e($details['email']) ?></a><?php else: ?><span dir="ltr"><?= e($details['email']) ?></span><?php endif; ?></div></li>
<?php endif; ?>
<?php if ($details['hours'] !== ''): ?>
        <li><span class="contact__icon"><?= svg_icon('clock') ?></span><div><b><?= e($view->t('site.contact.hours')) ?></b><span><?= e($details['hours']) ?></span></div></li>
<?php endif; ?>
      </ul>
    </aside>
    <form class="contact__form reveal" id="contact-form" method="post" action="<?= e_url($form['action']) ?>#contact-form" novalidate>
      <?= $view->csrfField() ?>
      <input type="hidden" name="<?= e_attr(\Gate\Security\SpamGuard::TIMESTAMP) ?>" value="<?= e_attr($form['token']) ?>">
      <div class="hp" aria-hidden="true"><label>Website <input type="text" name="<?= e_attr(\Gate\Security\SpamGuard::HONEYPOT) ?>" tabindex="-1" autocomplete="off"></label></div>
      <h2 class="h3"><?= e($view->t('site.contact.form_title')) ?></h2>
<?php if (isset($form['errors']['form'])): ?>
      <p class="form-alert" role="alert"><?= svg_icon('alert') ?><?= e($form['errors']['form']) ?></p>
<?php elseif ($form['errors'] !== []): ?>
      <p class="form-alert" role="alert"><?= svg_icon('alert') ?><?= e($view->t('site.form.check_fields')) ?></p>
<?php endif; ?>
      <div class="fields-2">
        <?= $field('name', $view->t('site.form.name'), 'text', true, 'name') ?>
        <?= $field('email', $view->t('site.form.email'), 'email', true, 'email', true) ?>
        <?= $field('phone', $view->t('site.form.phone'), 'tel', false, 'tel', true) ?>
        <?= $field('organisation', $view->t('site.form.organisation'), 'text', false, 'organization') ?>
      </div>
      <div class="field<?= $err('subject') !== null ? ' has-error' : '' ?>">
        <label for="f-subject"><?= e($view->t('site.form.subject')) ?> <span class="req" aria-hidden="true">*</span></label>
        <select id="f-subject" name="subject" required<?= $err('subject') !== null ? ' aria-invalid="true" aria-describedby="f-subject-error"' : '' ?>>
          <option value=""><?= e($view->t('site.form.choose')) ?></option>
<?php foreach ($form['subjects'] as $subject): ?>
          <option value="<?= e_attr($subject) ?>"<?= $v('subject') === $subject ? ' selected' : '' ?>><?= e($view->t('site.form.subjects.' . $subject)) ?></option>
<?php endforeach; ?>
        </select>
<?php if ($err('subject') !== null): ?>
        <p class="field__error" id="f-subject-error"><?= svg_icon('alert') ?><?= e((string) $err('subject')) ?></p>
<?php endif; ?>
      </div>
      <div class="field<?= $err('message') !== null ? ' has-error' : '' ?>">
        <label for="f-message"><?= e($view->t('site.form.message')) ?> <span class="req" aria-hidden="true">*</span></label>
        <textarea id="f-message" name="message" rows="6" required maxlength="4000"<?= $err('message') !== null ? ' aria-invalid="true" aria-describedby="f-message-error"' : '' ?>><?= e($v('message')) ?></textarea>
<?php if ($err('message') !== null): ?>
        <p class="field__error" id="f-message-error"><?= svg_icon('alert') ?><?= e((string) $err('message')) ?></p>
<?php endif; ?>
      </div>
      <div class="field field--check<?= $err('consent') !== null ? ' has-error' : '' ?>">
        <label class="check"><input type="checkbox" name="consent" value="1"<?= !empty($form['values']['consent']) ? ' checked' : '' ?> required<?= $err('consent') !== null ? ' aria-invalid="true" aria-describedby="f-consent-error"' : '' ?>>
          <span><?= e($view->t('site.form.consent')) ?><?php if ($form['privacyHref'] !== null): ?> <a href="<?= e_url($form['privacyHref']) ?>"><?= e($view->t('site.form.privacy_link')) ?></a><?php endif; ?></span></label>
<?php if ($err('consent') !== null): ?>
        <p class="field__error" id="f-consent-error"><?= svg_icon('alert') ?><?= e((string) $err('consent')) ?></p>
<?php endif; ?>
      </div>
      <button class="btn btn--primary" type="submit"><?= e($view->t('site.form.send')) ?> <?= svg_icon('arrow', 'ic ic--arrow') ?></button>
    </form>
  </div>
</section>
