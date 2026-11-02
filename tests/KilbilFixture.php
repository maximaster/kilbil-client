<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use RuntimeException;

use function assert;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Reads a recorded kilbil response body committed under
 * `fixtures/responses/`.
 */
final class KilbilFixture
{
    public static function raw(string $name): string
    {
        $path = __DIR__ . '/../fixtures/responses/' . $name . '.json';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('no fixture at %s', $path));
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(string $name): array
    {
        /** @var mixed $decoded */
        $decoded = json_decode(self::raw($name), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($decoded));

        $out = [];

        foreach ($decoded as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }
}
