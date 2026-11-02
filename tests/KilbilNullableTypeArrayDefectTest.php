<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use Maximaster\Kilbil\Generated\Enum\SearchClientRequestContentSearchMode;
use Maximaster\Kilbil\Generated\Model\SearchClient200Content;
use Maximaster\Kilbil\Generated\Model\SearchClientRequestContent;
use PHPUnit\Framework\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * `state` and `special_days_types` are declared `type: [T, "null"]`, so either
 * may arrive as JSON `null`. Both used to fail hydration with a `TypeError`;
 * these probes pin the nullable result.
 */
final class KilbilNullableTypeArrayDefectTest extends TestCase
{
    public function testStateNullHydratesToNullInsteadOfThrowing(): void
    {
        $data = KilbilFixture::decode('searchclient-found');
        $data['state'] = null;

        $response = $this->searchClientWith($data);

        self::assertNull($response->state);
    }

    public function testSpecialDaysTypesNullHydratesToNullInsteadOfThrowing(): void
    {
        $data = KilbilFixture::decode('searchclient-found');
        $data['special_days_types'] = null;

        $response = $this->searchClientWith($data);

        self::assertNull($response->special_days_types);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function searchClientWith(array $data): SearchClient200Content
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $http = new RecordingClient(KilbilHttpHarness::jsonResponse(200, $body));
        $client = KilbilHttpHarness::client($http);

        $result = $client->customers()->searchClient(new SearchClientRequestContent(
            search_mode: SearchClientRequestContentSearchMode::_1,
            search_value: '79129999999',
        ));

        self::assertInstanceOf(SearchClient200Content::class, $result->body);

        return $result->body;
    }
}
