<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use Maximaster\Kilbil\Generated\ApiClient;
use Maximaster\Kilbil\Generated\ApiException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Poagen\Runtime\Http\HttpTransport;

/**
 * Wires the real, committed kilbil `ApiClient` over the real `HttpTransport`
 * — the same runtime a consumer gets by default — against a PSR-18 stub that
 * never leaves the process. Nothing here is a substitute transport: the
 * point is proving `PsrRequestBuilder`, `Marshaller`, `Hydrator` and
 * `ResponseDecoder` all actually run end to end.
 */
final class KilbilHttpHarness
{
    public const string BASE_URI = 'https://kilbil.example/api';

    public static function client(RecordingClient $http, string $h = 'kilbil-test-key'): ApiClient
    {
        $factory = new Psr17Factory();

        $transport = new HttpTransport(
            self::BASE_URI,
            ApiException::class,
            $http,
            $factory,
            $factory,
        );

        return new ApiClient($transport, $h);
    }

    public static function jsonResponse(int $status, string $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }
}
