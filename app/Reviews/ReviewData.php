<?php

declare(strict_types=1);

namespace BMMatic\Reviews;

/**
 * One review as a provider hands it over, before it is stored. Values are normalised here (rating 1–5, dates as
 * "Y-m-d H:i:s", text without control characters), so every provider produces the same shape.
 */
final class ReviewData
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $reviewerName,
        public readonly int $rating,
        public readonly string $text,
        public readonly string $reviewDate,
        public readonly string $language = '',
        public readonly string $photoUrl = '',
        public readonly string $ownerReply = '',
        public readonly ?string $ownerReplyAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row provider values, already mapped to our field names
     */
    public static function fromArray(array $row, string $fallbackIdPrefix = ''): ?self
    {
        $name = self::line((string) ($row['reviewer_name'] ?? ''), 160);
        $text = self::multiline((string) ($row['text'] ?? ''));
        $rating = (int) round((float) ($row['rating'] ?? 0));
        $date = self::date((string) ($row['review_date'] ?? ''));
        if ($rating < 1 || $rating > 5 || $date === null) {
            return null;
        }
        $externalId = self::line((string) ($row['external_id'] ?? ''), 190);
        if ($externalId === '') {
            // Manual imports rarely carry an id: derive a stable one, so re-importing the same file updates.
            $externalId = $fallbackIdPrefix . substr(hash('sha256', $name . '|' . $date . '|' . $rating . '|' . mb_substr($text, 0, 200)), 0, 40);
        }
        $replyAt = self::date((string) ($row['owner_reply_at'] ?? ''));
        return new self(
            $externalId,
            $name === '' ? '—' : $name,
            $rating,
            $text,
            $date,
            self::languageCode((string) ($row['language'] ?? '')),
            self::photoUrl((string) ($row['photo_url'] ?? '')),
            self::multiline((string) ($row['owner_reply'] ?? '')),
            $replyAt,
        );
    }

    /** @return array<string, mixed> the row as it is stored (without the columns the workshop owns) */
    public function toRow(string $source, string $now): array
    {
        return [
            'google_review_id' => $this->externalId,
            'reviewer_name' => $this->reviewerName,
            'reviewer_photo_url' => $this->photoUrl,
            'rating' => $this->rating,
            'text' => $this->text,
            'language' => $this->language,
            'review_date' => $this->reviewDate,
            'owner_reply' => $this->ownerReply === '' ? null : $this->ownerReply,
            'owner_reply_at' => $this->ownerReplyAt,
            'source' => $source,
            'deleted_at' => null,
            'updated_at' => $now,
        ];
    }

    /**
     * True when the stored row differs from this review (so a sync only writes what changed).
     *
     * @param array<string, mixed> $row
     */
    public function differsFrom(array $row): bool
    {
        return (string) $row['reviewer_name'] !== $this->reviewerName
            || (int) $row['rating'] !== $this->rating
            || (string) ($row['text'] ?? '') !== $this->text
            || (string) $row['language'] !== $this->language
            || substr((string) $row['review_date'], 0, 19) !== $this->reviewDate
            || (string) ($row['owner_reply'] ?? '') !== $this->ownerReply
            || (string) $row['reviewer_photo_url'] !== $this->photoUrl
            || $row['deleted_at'] !== null;
    }

    private static function line(string $value, int $max): string
    {
        $value = (string) preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value);
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $value)), 0, $max);
    }

    private static function multiline(string $value, int $max = 5000): string
    {
        $value = str_replace("\r\n", "\n", $value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return mb_substr(trim($value), 0, $max);
    }

    /** Accepts ISO 8601 (Google), "Y-m-d H:i" and "d/m/Y"; anything else is refused. */
    private static function date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m) === 1) {
            $value = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        $time = strtotime($value);
        return $time === false ? null : gmdate('Y-m-d H:i:s', $time);
    }

    private static function languageCode(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $value) === 1 ? mb_substr($value, 0, 5) : '';
    }

    private static function photoUrl(string $value): string
    {
        $value = trim($value);
        return preg_match('#^https://[^\s<>"]{6,500}$#', $value) === 1 ? $value : '';
    }
}
