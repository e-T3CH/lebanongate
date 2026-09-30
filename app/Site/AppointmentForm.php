<?php

declare(strict_types=1);

namespace Gate\Site;

/**
 * The appointment request form: fields as drawn (name, phone, car make & model, gearbox type, symptoms) plus email and
 * the required privacy consent. Server-side validation returns translation keys per field; values are normalised and
 * kept so the form can be shown again after an error.
 *
 * @phpstan-type Values array{name: string, phone: string, email: string, car: string, gearbox_type: string, symptoms: string, consent: bool}
 */
final class AppointmentForm
{
    public const GEARBOX_TYPES = ['automatic', 'dual_clutch', 'cvt', 'automated_manual', 'unknown'];
    public const LIMITS = ['name' => 120, 'phone' => 40, 'email' => 190, 'car' => 160, 'symptoms' => 2000];

    /**
     * @param array<string, mixed> $input raw POST data
     * @return Values
     */
    public static function values(array $input): array
    {
        $text = static function (mixed $v, bool $multiline = false): string {
            $v = is_scalar($v) ? (string) $v : '';
            $v = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
            $v = $multiline ? str_replace("\r\n", "\n", $v) : (string) preg_replace('/\s+/u', ' ', $v);
            return trim($v);
        };
        $type = $text($input['gearbox_type'] ?? '');
        return [
            'name' => $text($input['name'] ?? ''),
            'phone' => $text($input['phone'] ?? ''),
            'email' => mb_strtolower($text($input['email'] ?? '')),
            'car' => $text($input['car'] ?? ''),
            'gearbox_type' => in_array($type, self::GEARBOX_TYPES, true) ? $type : '',
            'symptoms' => $text($input['symptoms'] ?? '', true),
            'consent' => in_array($input['consent'] ?? null, ['1', 'on', 'yes', true], true),
        ];
    }

    /**
     * @param Values $values
     * @return array<string, array{0: string, 1: array<string, int|string>}> field => [translation key, parameters]
     */
    public static function validate(array $values): array
    {
        $errors = [];
        foreach (['name', 'phone', 'email', 'car', 'symptoms'] as $field) {
            if ($values[$field] === '') {
                $errors[$field] = ['validation.required', []];
            } elseif (mb_strlen($values[$field]) > self::LIMITS[$field]) {
                $errors[$field] = ['validation.max_length', ['max' => self::LIMITS[$field]]];
            }
        }
        if (!isset($errors['phone']) && (preg_match('/^\+?[0-9 ().\/-]{6,40}$/', $values['phone']) !== 1 || strlen((string) preg_replace('/\D/', '', $values['phone'])) < 8)) {
            $errors['phone'] = ['validation.phone', []];
        }
        if (!isset($errors['email']) && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = ['validation.email', []];
        }
        if (!isset($errors['name']) && mb_strlen($values['name']) < 2) {
            $errors['name'] = ['validation.min_length', ['min' => 2]];
        }
        if (!isset($errors['symptoms']) && mb_strlen($values['symptoms']) < 5) {
            $errors['symptoms'] = ['validation.min_length', ['min' => 5]];
        }
        if ($values['gearbox_type'] === '') {
            $errors['gearbox_type'] = ['validation.choice', []];
        }
        if (!$values['consent']) {
            $errors['consent'] = ['validation.consent', []];
        }
        return $errors;
    }
}
