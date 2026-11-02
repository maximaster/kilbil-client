<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use DateTimeImmutable;
use Maximaster\Kilbil\Generated\Enum\SearchClient200ContentActivateClientType;
use Maximaster\Kilbil\Generated\Enum\SearchClient200ContentInterfacesItemInterfaceType;
use Maximaster\Kilbil\Generated\Enum\SearchClient200ContentProgramType;
use Maximaster\Kilbil\Generated\Enum\SearchClientRequestContentSearchMode;
use Maximaster\Kilbil\Generated\Model\SearchClient200Content;
use Maximaster\Kilbil\Generated\Model\SearchClient200ContentCardsItem;
use Maximaster\Kilbil\Generated\Model\SearchClient200ContentInterfacesItem;
use Maximaster\Kilbil\Generated\Model\SearchClientRequestContent;
use Maximaster\Kilbil\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

/**
 * Hydrates `searchClient()` against a response assembled from the vendor's own
 * per-property `example:` values in `schemas/loyalty_v1.yaml`, not invented
 * ones. See `fixtures/responses/searchclient-found.json`.
 *
 * Three deviations are deliberate. `sms_code_sum`, `card_state` and `sex`
 * follow the declared JSON type where the vendor's example contradicts it.
 * `cards[0].outer_systems_interface_name` reuses the value the sibling
 * `getclientcards` operation documents, because copying the vendor's `null`
 * would fail hydration on the example rather than on the client. And
 * `outer_systems_interface_type` uses `10`, one of the property's own
 * documented enum values, because the vendor supplies no example at all.
 */
final class KilbilRecordedResponseTest extends TestCase
{
    public function testATypeListNullPropertyAndAPresentOneBothHydrate(): void
    {
        $response = $this->searchClientFound();

        // program_write_off_type: type: [string, "null"], vendor's own
        // example is the literal null (schemas/loyalty_v1.yaml:803).
        self::assertNull($response->program_write_off_type);
        // client_id: type: [string, "null"], vendor's own example is
        // '4004565' (schemas/loyalty_v1.yaml:364).
        self::assertSame('4004565', $response->client_id);
    }

    public function testANestedListOfObjectsHydratesToTypedItems(): void
    {
        $response = $this->searchClientFound();

        self::assertCount(1, $response->cards ?? []);
        $card = ($response->cards ?? [])[0];
        self::assertInstanceOf(SearchClient200ContentCardsItem::class, $card);
        self::assertSame(10963328, $card->card_id);
        self::assertSame('0061', $card->code);

        self::assertCount(1, $response->interfaces ?? []);
        $interface = ($response->interfaces ?? [])[0];
        self::assertInstanceOf(SearchClient200ContentInterfacesItem::class, $interface);
        self::assertSame('Telegram', $interface->interface_name);
        self::assertSame(SearchClient200ContentInterfacesItemInterfaceType::_0, $interface->interface_type);

        // A second nested list, doubly nested under `_bill_data`.
        $billItems = $response->_bill_data->items ?? [];
        self::assertCount(2, $billItems);
        self::assertSame('2137', $billItems[0]->code);
        self::assertSame(130.0, $billItems[0]->price);
        self::assertNull($billItems[0]->new_price_action_id);
        self::assertSame(32243, $billItems[1]->new_price_action_id);
    }

    public function testANestedListItemsOwnNullableDateHydratesBothWays(): void
    {
        $response = $this->searchClientFound();

        $specialDays = $response->special_days_types ?? [];
        self::assertCount(2, $specialDays);
        self::assertInstanceOf(DateTimeImmutable::class, $specialDays[0]->value);
        self::assertSame('2017-07-07', $specialDays[0]->value->format('Y-m-d'));
        self::assertNull($specialDays[1]->value);
    }

    public function testAPropertyBoundToAConsumerValueObjectHydrates(): void
    {
        $response = $this->searchClientFound();

        self::assertInstanceOf(PhoneNumber::class, $response->phone);
        self::assertSame('79129999999', $response->phone->digits);
    }

    public function testEnumValuedPropertiesHydrateToBackedEnumCases(): void
    {
        $response = $this->searchClientFound();

        self::assertSame(SearchClient200ContentActivateClientType::_1, $response->activate_client_type);
        self::assertSame(SearchClient200ContentProgramType::_0, $response->program_type);
    }

    private function searchClientFound(): SearchClient200Content
    {
        $http = new RecordingClient(KilbilHttpHarness::jsonResponse(200, KilbilFixture::raw('searchclient-found')));
        $client = KilbilHttpHarness::client($http);

        $result = $client->customers()->searchClient(new SearchClientRequestContent(
            search_mode: SearchClientRequestContentSearchMode::_1,
            search_value: '79129999999',
        ));

        self::assertInstanceOf(SearchClient200Content::class, $result->body);

        return $result->body;
    }
}
