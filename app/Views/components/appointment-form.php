<?php
/**
 * Appointment request form as drawn: name, phone, car, gearbox type, email, symptoms and the required privacy consent,
 * with a honeypot, the signed time-trap token and the CSRF token. Server-side errors are shown per field (linked with
 * aria-describedby) and summarised above the form; entered values are kept. ui.js disables the submit button while
 * sending (no double submit).
 * Parameters: Components::SPECS['appointment-form'].
 *
 * @var \Gate\Core\View $view
 * @var array{action: string, title: string, submitLabel: string, token: string, values: array<string, mixed>, errors: array<string, mixed>, privacyHref: ?string, idPrefix: string, selectName: string, origin: string, class: string} $p
 */

use Gate\Core\Html;
use Gate\Core\Props;
use Gate\Security\SpamGuard;
use Gate\Site\AppointmentForm;

$id = static fn (string $suffix): string => $p['idPrefix'] . '-' . $suffix;
$value = static fn (string $field): ?string => is_scalar($p['values'][$field] ?? null) ? (string) $p['values'][$field] : null;
$error = static fn (string $field): ?string => is_string($p['errors'][$field] ?? null) ? $p['errors'][$field] : null;
$options = array_map(static fn (string $type): array => ['value' => $type, 'label' => $view->t('site.form.types.' . $type)], AppointmentForm::GEARBOX_TYPES);
$messages = array_values(array_filter($p['errors'], 'is_string'));
$consentText = $p['privacyHref'] !== null
    ? e($view->t('site.form.consent_before')) . ' <a href="' . e_url($p['privacyHref']) . '">' . e($view->t('site.form.consent_link')) . '</a>' . e($view->t('site.form.consent_after'))
    : e($view->t('site.form.consent_before') . ' ' . $view->t('site.form.consent_link') . $view->t('site.form.consent_after'));
$consentError = $error('consent');
$consent = '<label class="form__consent">' . $view->component('toggle', ['name' => 'consent', 'id' => $id('consent'), 'required' => true, 'uncheckedValue' => null, 'checked' => ($p['values']['consent'] ?? false) === true, 'attrs' => $consentError !== null ? ['aria-invalid' => 'true', 'aria-describedby' => $id('consent') . '-error'] : []]) . ' <span>' . $consentText . '</span></label>';
if ($consentError !== null) {
    $consent = '<div class="form__consent-wrap">' . $consent . $view->component('form-error', ['message' => $consentError, 'id' => $id('consent') . '-error']) . '</div>';
}
$field = static fn (string $name, string $suffix, string $type, string $autocomplete = ''): Html => $view->component('input', array_filter([
    'id' => $id($suffix), 'name' => $name, 'type' => $type, 'label' => $view->t('site.form.' . $name), 'placeholder' => $view->t('site.form.' . $name . '_placeholder'),
    'value' => $value($name), 'autocomplete' => $autocomplete !== '' ? $autocomplete : null, 'error' => $error($name), 'required' => true,
], static fn ($v): bool => $v !== null));
?>
<form class="<?= e_attr(Props::classes('form', $p['class'])) ?>" action="<?= e_url($p['action']) ?>" method="post" novalidate data-once>
    <div class="form__title"><?= e($p['title']) ?></div>
<?php if ($messages !== []): ?>
    <?= $view->component('form-errors', ['variant' => 'public', 'title' => $view->t('site.form.errors_title'), 'messages' => []]) ?>

<?php endif; ?>
    <?= $view->csrfField() ?><input type="hidden" name="<?= SpamGuard::TIMESTAMP ?>" value="<?= e_attr($p['token']) ?>"><input type="hidden" name="origin" value="<?= e_attr($p['origin']) ?>">
    <div class="form__grid">
      <?= $field('name', 'name', 'text', 'name') ?><?= $field('phone', 'phone', 'tel', 'tel') ?><?= $field('car', 'car', 'text') ?><?= $view->component('select', ['id' => $id('type'), 'name' => $p['selectName'], 'label' => $view->t('site.form.type'), 'options' => $options, 'value' => $value('gearbox_type') ?? 'automatic', 'error' => $error('gearbox_type'), 'required' => true]) ?>

    </div>
    <?= $field('email', 'email', 'email', 'email') ?>
    <?= $view->component('textarea', ['id' => $id('msg'), 'name' => 'symptoms', 'label' => $view->t('site.form.symptoms'), 'placeholder' => $view->t('site.form.symptoms_placeholder'), 'value' => $value('symptoms'), 'error' => $error('symptoms'), 'required' => true, 'maxlength' => AppointmentForm::LIMITS['symptoms']]) ?>

    <div class="hp" aria-hidden="true"><label for="<?= e_attr($id('website')) ?>"><?= e($view->t('site.form.honeypot')) ?></label><input id="<?= e_attr($id('website')) ?>" name="<?= SpamGuard::HONEYPOT ?>" type="text" tabindex="-1" autocomplete="off"></div>
    <?= $consent ?>

    <?= $view->component('button', ['label' => $p['submitLabel'], 'type' => 'submit', 'icon' => 'fa-solid fa-arrow-right', 'class' => 'form__send', 'attrs' => ['data-loading-label' => $view->t('site.form.sending')]]) ?>

  </form>
