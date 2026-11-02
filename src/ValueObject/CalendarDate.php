<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Poagen\Runtime\ScalarBinding;

use function is_string;

/**
 * A kilbil calendar date.
 *
 * `data` (1 use) is `date` mistyped. The correctly spelled fields use the
 * built-in binding and still arrive as `DateTimeImmutable`; this class covers
 * only the typo.
 */
final readonly class CalendarDate implements ScalarBinding
{
    private const string WIRE = 'Y-m-d';

    public function __construct(public DateTimeImmutable $value) {}

    public static function fromScalar(mixed $raw): self
    {
        if (!is_string($raw)) {
            throw new InvalidArgumentException(
                'kilbil date expects a string payload, got ' . get_debug_type($raw),
            );
        }

        // The `!` resets the time fields, so a date-only value does not pick up
        // the current clock and make output non-deterministic.
        $parsed = DateTimeImmutable::createFromFormat('!' . self::WIRE, $raw);

        return new self($parsed instanceof DateTimeImmutable ? $parsed : new DateTimeImmutable($raw));
    }

    public function toScalar(): string
    {
        return $this->value->format(self::WIRE);
    }
}
