<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use Maximaster\Kilbil\Generated\Model\AddClient200Content;
use Maximaster\Kilbil\Generated\Model\AskConfirmPhone200Content;
use Maximaster\Kilbil\Generated\Model\ProcessSale200Content;
use PHPUnit\Framework\TestCase;
use Poagen\Runtime\Marshal\Hydrator;

/**
 * Regressions for the vendor's inconsistencies that the schema patches answer:
 * a string `client_id` where the document says integer, `result_text: null` on
 * a successful confirmation-code send, and a reversal whose `processsale`
 * response carries neither `_bill_data` nor the bill presentation fields.
 *
 * Each probe hydrates through the real {@see Hydrator}, the same path an HTTP
 * response takes, so a type regression in `generated/` fails here rather than
 * at a consumer's first live call.
 */
final class KilbilVendorQuirkHydrationTest extends TestCase
{
    public function testAddClientHydratesTheStringClientIdTheServerSends(): void
    {
        $body = [
            'success' => 1,
            '_client_id' => '4004565',
            'client_id' => '4004565',
            'result_code' => '0',
            'result_text' => 'Данные клиента изменены',
        ];

        $model = new Hydrator()->hydrate(AddClient200Content::class, $body);

        self::assertInstanceOf(AddClient200Content::class, $model);
        self::assertSame('4004565', $model->client_id);
    }

    public function testAskConfirmPhoneHydratesTheNullResultTextTheServerSendsOnSuccess(): void
    {
        $body = [
            'success' => 1,
            'result_code' => '0',
            'result_text' => null,
            'type_confirmation_code' => '0',
        ];

        $model = new Hydrator()->hydrate(AskConfirmPhone200Content::class, $body);

        self::assertInstanceOf(AskConfirmPhone200Content::class, $model);
        self::assertNull($model->result_text);
    }

    public function testProcessSaleHydratesANullBillDataOnAReversal(): void
    {
        $body = [
            'result_code' => '0',
            'result_text' => 'Чек обработан',
            '_bill_data' => null,
        ];

        $model = new Hydrator()->hydrate(ProcessSale200Content::class, $body);

        self::assertInstanceOf(ProcessSale200Content::class, $model);
        self::assertNull($model->_bill_data);
    }

    public function testProcessSaleHydratesWithoutTheOptionalBillPresentationFields(): void
    {
        $body = [
            'result_code' => '0',
            'result_text' => 'Чек обработан',
        ];

        $model = new Hydrator()->hydrate(ProcessSale200Content::class, $body);

        self::assertInstanceOf(ProcessSale200Content::class, $model);
        self::assertNull($model->_bill_body);
        self::assertNull($model->_bill_footer);
        self::assertNull($model->_bill_header);
    }
}
