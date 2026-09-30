<?php

declare(strict_types=1);

namespace Gate\Site;

use Gate\Repositories\MessageRepository;

/**
 * The contact form: name, email, phone and organisation (optional), subject, message and the required privacy consent.
 * Server-side validation returns translation keys per field; values are normalised and kept so the form can be shown
 * again after an error.
 *
 * @phpstan-type Values array{name: string, email: string, phone: string, organisation: string, subject: string, message: string, consent: bool}
 */
final class ContactForm
{
    public const LIMITS = ['name' => 120, 'email' => 190, 'phone' => 40, 'organisation' => 160, 'message' => 4000];

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
        $subject = $text($input['subject'] ?? '');
        return [
            'name' => $text($input['name'] ?? ''),
            'email' => mb_strtolower($text($input['email'] ?? '')),
            'phone' => $text($input['phone'] ?? ''),
            'organisation' => $text($input['organisation'] ?? ''),
            'subject' => in_array($subject, MessageRepository::SUBJECTS, true) ? $subject : '',
            'message' => $text($input['message'] ?? '', true),
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
        foreach (['name', 'email', 'message'] as $field) {
            if ($values[$field] === '') {
                $errors[$field] = ['validation.required', []];
            }
        }
        foreach (self::LIMITS as $field => $max) {
            if (!isset($errors[$field]) && mb_strlen($values[$field]) > $max) {
                $errors[$field] = ['validation.max_length', ['max' => $max]];
            }
        }
        if (!isset($errors['name']) && mb_strlen($values['name']) < 2) {
            $errors['name'] = ['validation.min_length', ['min' => 2]];
        }
        if (!isset($errors['email']) && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = ['validation.email', []];
        }
        if (!isset($errors['phone']) && $values['phone'] !== '' && (preg_match('/^\+?[0-9 ().\/-]{6,40}$/', $values['phone']) !== 1 || strlen((string) preg_replace('/\D/', '', $values['phone'])) < 7)) {
            $errors['phone'] = ['validation.phone', []];
        }
        if (!isset($errors['message']) && mb_strlen($values['message']) < 10) {
            $errors['message'] = ['validation.min_length', ['min' => 10]];
        }
        if ($values['subject'] === '') {
            $errors['subject'] = ['validation.choice', []];
        }
        if (!$values['consent']) {
            $errors['consent'] = ['validation.consent', []];
        }
        return $errors;
    }
}
