<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use Maximaster\Kilbil\Generated\Enum\SearchClientRequestContentSearchMode;
use Maximaster\Kilbil\Generated\Model\SearchClient403Content;
use Maximaster\Kilbil\Generated\Model\SearchClientRequestContent;
use PHPUnit\Framework\TestCase;

use function urlencode;

/**
 * The request side: the `h` query-string credential reaches an actually-built
 * request, and a declared 403 hydrates onto the envelope instead of throwing.
 */
final class KilbilRequestAndErrorTest extends TestCase
{
    public function testTheHCredentialReachesTheQueryStringOfAnActuallyBuiltRequest(): void
    {
        $http = new RecordingClient(KilbilHttpHarness::jsonResponse(200, KilbilFixture::raw('searchclient-found')));
        $client = KilbilHttpHarness::client($http, 'super-secret-h-value');

        $client->customers()->searchClient(new SearchClientRequestContent(
            search_mode: SearchClientRequestContentSearchMode::_1,
            search_value: '79129999999',
        ));

        self::assertNotNull($http->lastRequest);
        $uri = $http->lastRequest->getUri();
        self::assertSame('/api/load/searchclient', $uri->getPath());
        // Query, not a header or the body — the scheme this document
        // declares (`h`, apiKey, in: query, global security).
        self::assertStringContainsString('h=' . urlencode('super-secret-h-value'), $uri->getQuery());
        self::assertStringNotContainsString('h', $http->lastRequest->getHeaderLine('Authorization'));
    }

    public function testA403ResponseHydratesIntoTheDeclaredErrorBodyRatherThanThrowing(): void
    {
        // Body from the vendor's own 403 example (schemas/loyalty_v1.yaml
        // ~985-992): result_code example 1, result_text example
        // 'Доступ запрещен'.
        $http = new RecordingClient(KilbilHttpHarness::jsonResponse(403, KilbilFixture::raw('error-403')));
        $client = KilbilHttpHarness::client($http, 'super-secret-h-value');

        $result = $client->customers()->searchClient(new SearchClientRequestContent(
            search_mode: SearchClientRequestContentSearchMode::_1,
            search_value: '79129999999',
        ));

        self::assertSame(403, $result->status);
        self::assertInstanceOf(SearchClient403Content::class, $result->body);
        self::assertSame(1, $result->body->result_code);
        self::assertSame('Доступ запрещен', $result->body->result_text);
        self::assertNotNull($http->lastRequest);
        self::assertSame(403, $result->response->getStatusCode());
    }
}
