<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\ValueObject;

use InvalidArgumentException;
use Poagen\Runtime\ScalarBinding;

use function is_string;

/**
 * A kilbil phone number.
 *
 * `phone` (4 uses) is not an OpenAPI format at all — the vendor invented it, and
 * their description says what it means: eleven digits beginning with a 7. That
 * constraint lives here rather than in the generated tree.
 */
final readonly class PhoneNumber implements ScalarBinding
{
    public function __construct(public string $digits)
    {
        if (preg_match('/^7\d{10}$/', $digits) !== 1) {
            throw new InvalidArgumentException(
                "kilbil expects an 11-digit phone number beginning with 7, got '{$digits}'",
            );
        }
    }

    public static function fromScalar(mixed $raw): self
    {
        if (!is_string($raw)) {
            throw new InvalidArgumentException(
                'kilbil phone expects a string payload, got ' . get_debug_type($raw),
            );
        }

        return new self($raw);
    }

    public function toScalar(): string
    {
        return $this->digits;
    }
}
