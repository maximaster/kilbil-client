<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Poagen\Runtime\BindingContext;
use Poagen\Runtime\ContextualScalarBinding;

use function is_string;

/**
 * A kilbil timestamp.
 *
 * The vendor writes this format three ways and gets it wrong all three times:
 * `date time` (11 uses) is `date-time` with a space where the hyphen belongs,
 * and `data time` (3 uses) is that typo plus another. poagen reports each one
 * rather than quietly leaving 14 timestamps as strings, and this class is the
 * answer — one type for both spellings, bound through the profile's
 * `formats` map.
 *
 * Implementing `ScalarBinding` is what keeps the wire honest: the generated
 * client sends and receives the vendor's string, and the consumer's code holds a
 * `DateTimeImmutable`. Nothing about the repair reaches the emitted tree.
 *
 * Note the format is bound to a *class*, not to `DateTimeImmutable` directly: an
 * attribute cannot be put on a built-in type, and a consumer wanting Carbon or
 * Chronos instead swaps only this file.
 */
final readonly class Timestamp implements ContextualScalarBinding
{
    /**
     * The vendor's own examples use a space between date and time rather than
     * the `T` separator RFC 3339 asks for, so this is what we SEND — and the
     * test server accepts it on `processsale`/`confirmsale` (verified live
     * 2026-08-06).
     */
    private const string WIRE = 'Y-m-d H:i:s';

    /**
     * What we ACCEPT on the way in. The vendor is asymmetric: it takes hyphen
     * dates on input but returns dotted ones on output — `getdetailingbymove`'s
     * `move_date` comes back as `2026.08.06 18:28:24` (verified live
     * 2026-08-06). `DateTimeImmutable`'s own parser chokes on that ("Double
     * time specification"), so both separators are tried explicitly, hyphen
     * first, before any permissive fallback.
     *
     * @var list<non-empty-string>
     */
    private const array WIRE_FORMATS = ['Y-m-d H:i:s', 'Y.m.d H:i:s'];

    /**
     * Kilbil sends and expects Moscow wall clock, and says so nowhere: every
     * timestamp in the document is a bare string with no offset and no prose
     * naming a zone. A string without an offset is resolved by PHP
     * against the PROCESS timezone, so without this const the same payload
     * means different instants on a developer's machine and in a UTC container
     * — which is exactly how a bonus history can read three hours late in a
     * UTC deployment.
     *
     * So the zone is stated here, once, and applied in BOTH directions: a
     * parsed value is a real instant, and a serialised one is Moscow wall
     * clock whatever zone the caller's `DateTimeImmutable` carried. Callers no
     * longer have to remember — before this, four separate classes each built
     * their own `Europe/Moscow` clock to send the right digits, and any fifth
     * that forgot would have written documents three hours early.
     *
     * This is an ASSUMPTION about the installation, not something the document
     * states, so it is a DEFAULT rather than the only answer: a consumer whose
     * Kilbil installation runs in another zone says so once, in the
     * `BindingContext` they hand the transport, and that wins here without a
     * line of this package changing. Which of the two is right for a given
     * installation is a question only the vendor can settle — see README.md.
     */
    private const string VENDOR_TZ = 'Europe/Moscow';

    public function __construct(public DateTimeImmutable $value) {}

    public static function fromScalar(mixed $raw): self
    {
        return self::fromScalarIn($raw, new BindingContext());
    }

    public static function fromScalarIn(mixed $raw, BindingContext $context): self
    {
        if (!is_string($raw)) {
            throw new InvalidArgumentException(
                'kilbil timestamp expects a string payload, got ' . get_debug_type($raw),
            );
        }

        foreach (self::WIRE_FORMATS as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $raw, self::zone($context));

            if ($parsed instanceof DateTimeImmutable) {
                return new self($parsed);
            }
        }

        // Some endpoints omit the time part entirely; fall back to a permissive
        // parse rather than throwing on a value the vendor considers valid.
        // The zone argument applies only to a string that carries no offset of
        // its own (verified on PHP 8.4.17), so a value that ever did arrive
        // RFC 3339-compliant keeps the offset it declared.
        try {
            return new self(new DateTimeImmutable($raw, self::zone($context)));
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf('kilbil timestamp could not be parsed: "%s".', $raw), $exception->getCode(), previous: $exception);
        }
    }

    public function toScalar(): string
    {
        return $this->toScalarIn(new BindingContext());
    }

    public function toScalarIn(BindingContext $context): string
    {
        return $this->value->setTimezone(self::zone($context))->format(self::WIRE);
    }

    /**
     * The consumer's statement when they made one, this package's assumption
     * otherwise — the precedence poagen's `BindingContext` exists to give.
     */
    private static function zone(BindingContext $context): DateTimeZone
    {
        return $context->naiveZone ?? new DateTimeZone(self::VENDOR_TZ);
    }
}
