<?php

declare(strict_types=1);

namespace BMMatic\Core;

/**
 * Markup that is already safe to output: the result of a component or template render.
 * Component slots accept Html (printed as is) or a plain string (always escaped), so text can never
 * reach the page unescaped by accident. Only View creates instances from rendered templates.
 */
final class Html implements \Stringable
{
    private function __construct(private readonly string $markup)
    {
    }

    /** @internal for View and trusted static markup produced by the application itself (never user input). */
    public static function trusted(string $markup): self
    {
        return new self($markup);
    }

    /** Joins several fragments; plain strings are escaped. */
    public static function join(self|string ...$parts): self
    {
        return new self(implode('', array_map(static fn (self|string $p): string => self::of($p), $parts)));
    }

    /** Output form of a slot value: Html as is, strings escaped. */
    public static function of(self|string|null $value): string
    {
        if ($value === null) {
            return '';
        }
        return $value instanceof self ? $value->markup : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public function isEmpty(): bool
    {
        return trim($this->markup) === '';
    }

    public function __toString(): string
    {
        return $this->markup;
    }
}
