<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

use BMMatic\Services\Settings;

/**
 * Manual import: a JSON or CSV file that the owner exports from Google (or keeps by hand), with a column mapper and
 * a preview before anything is stored. Useful while API access is still pending, and as a fallback if a connection
 * ever breaks.
 *
 * As a provider it has nothing to fetch — a sync with this source keeps what is in the database — so fetch() simply
 * reports that. Importing goes through parse() and the preview on the admin screen.
 */
final class ManualImportProvider implements ReviewProvider
{
    /** The fields an import can fill; everything else in the file is ignored. */
    public const FIELDS = ['reviewer_name', 'rating', 'text', 'review_date', 'language', 'external_id', 'owner_reply'];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function key(): string
    {
        return 'manual';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function limits(): array
    {
        return ['max_reviews' => null, 'note' => 'manual', 'needs_oauth' => false];
    }

    public function fetch(): ProviderResult
    {
        // Nothing to poll: the file is the source. The stored rating and count stay as they were entered.
        $rating = (float) $this->settings->string('reviews.rating');
        $count = (int) preg_replace('/\D/', '', $this->settings->string('reviews.count'));
        return ProviderResult::success([], $rating > 0 ? $rating : null, $count > 0 ? $count : null);
    }

    /**
     * Reads an uploaded file into rows of raw values plus the column names it found.
     * JSON may be a list of objects or {reviews: [...]}; CSV uses the first row as its header.
     *
     * @return array{columns: list<string>, rows: list<array<string, string>>}|array{error: string}
     */
    public static function parse(string $contents, string $filename = ''): array
    {
        $contents = ltrim($contents, "\xEF\xBB\xBF");
        if (trim($contents) === '') {
            return ['error' => 'empty'];
        }
        $isJson = str_starts_with(ltrim($contents), '[') || str_starts_with(ltrim($contents), '{') || str_ends_with(strtolower($filename), '.json');
        return $isJson ? self::parseJson($contents) : self::parseCsv($contents);
    }

    /**
     * Applies the column mapping (our field => column in the file) to the parsed rows.
     *
     * @param list<array<string, string>> $rows
     * @param array<string, string> $mapping
     * @return list<ReviewData>
     */
    public static function map(array $rows, array $mapping): array
    {
        $out = [];
        foreach ($rows as $row) {
            $values = [];
            foreach (self::FIELDS as $field) {
                $column = $mapping[$field] ?? '';
                $values[$field === 'text' ? 'text' : $field] = $column !== '' && isset($row[$column]) ? $row[$column] : '';
            }
            $review = ReviewData::fromArray($values, 'manual-');
            if ($review !== null) {
                $out[] = $review;
            }
        }
        return $out;
    }

    /**
     * Guesses the mapping from the column names, so the preview is usually right without touching it.
     *
     * @param list<string> $columns
     * @return array<string, string>
     */
    public static function guessMapping(array $columns): array
    {
        $hints = [
            'reviewer_name' => ['reviewer_name', 'reviewer', 'author', 'name', 'naam', 'auteur', 'displayname'],
            'rating' => ['rating', 'stars', 'score', 'sterren', 'note'],
            'text' => ['text', 'comment', 'review', 'tekst', 'commentaire', 'avis'],
            'review_date' => ['review_date', 'date', 'created', 'createtime', 'publishtime', 'datum'],
            'language' => ['language', 'lang', 'languagecode', 'taal', 'langue'],
            'external_id' => ['external_id', 'id', 'reviewid', 'review_id'],
            'owner_reply' => ['owner_reply', 'reply', 'response', 'antwoord', 'reponse'],
        ];
        $mapping = [];
        foreach ($hints as $field => $names) {
            foreach ($columns as $column) {
                $normalised = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $column));
                if (in_array($normalised, array_map(static fn (string $n): string => str_replace('_', '', $n), $names), true)) {
                    $mapping[$field] = $column;
                    break;
                }
            }
        }
        return $mapping;
    }

    /** @return array{columns: list<string>, rows: list<array<string, string>>}|array{error: string} */
    private static function parseJson(string $contents): array
    {
        $data = json_decode($contents, true);
        if (!is_array($data)) {
            return ['error' => 'not_readable'];
        }
        if (isset($data['reviews']) && is_array($data['reviews'])) {
            $data = $data['reviews'];
        }
        $rows = [];
        $columns = [];
        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $row = [];
            foreach (self::flatten($entry) as $key => $value) {
                $row[$key] = $value;
                if (!in_array($key, $columns, true)) {
                    $columns[] = $key;
                }
            }
            $rows[] = $row;
        }
        return $rows === [] ? ['error' => 'no_rows'] : ['columns' => $columns, 'rows' => $rows];
    }

    /** @return array{columns: list<string>, rows: list<array<string, string>>}|array{error: string} */
    private static function parseCsv(string $contents): array
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return ['error' => 'not_readable'];
        }
        fwrite($handle, $contents);
        rewind($handle);
        $firstLine = explode("\n", $contents)[0];
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $header = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!is_array($header)) {
            fclose($handle);
            return ['error' => 'not_readable'];
        }
        $columns = array_map(static fn ($h): string => trim((string) $h), $header);
        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($line === [null]) {
                continue;
            }
            $row = [];
            foreach ($columns as $i => $column) {
                $row[$column] = isset($line[$i]) ? (string) $line[$i] : '';
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows === [] ? ['error' => 'no_rows'] : ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * Flattens one nested entry ("reviewer.displayName") so the mapper can offer every value as a column.
     *
     * @param array<mixed> $entry
     * @return array<string, string>
     */
    private static function flatten(array $entry, string $prefix = ''): array
    {
        $out = [];
        foreach ($entry as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
            if (is_array($value)) {
                $out += self::flatten($value, $name);
            } elseif (is_scalar($value) || $value === null) {
                $out[$name] = (string) $value;
            }
        }
        return $out;
    }
}
