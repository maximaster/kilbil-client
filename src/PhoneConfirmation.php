<?php

declare(strict_types=1);

namespace Maximaster\Kilbil;

use Maximaster\Kilbil\Generated\ConfirmationCodesClientInterface;
use Maximaster\Kilbil\Generated\Model\AskConfirmPhone200Content;
use Maximaster\Kilbil\Generated\Model\AskConfirmPhone403Content;
use Maximaster\Kilbil\Generated\Model\AskConfirmPhoneRequestContent;
use Maximaster\Kilbil\Generated\Model\CheckConfirmPhoneCode200Content;
use Maximaster\Kilbil\Generated\Model\CheckConfirmPhoneCode403Content;
use Maximaster\Kilbil\Generated\Model\CheckConfirmPhoneCodeRequestContent;
use Maximaster\Kilbil\ValueObject\PhoneNumber;
use Poagen\Runtime\Contract\Envelope;

/**
 * Hand-written convenience around the generated confirmation-code client.
 *
 * Implements the emitted `ConfirmationCodesClientInterface`, delegates both
 * calls, and adds the one method the vendor's document cannot express: a phone
 * number and a code in, a yes or no out. The seam is compiler-checked, so a
 * regeneration that renames `checkConfirmPhoneCode` fails the build here.
 */
final readonly class PhoneConfirmation implements ConfirmationCodesClientInterface
{
    public function __construct(
        private ConfirmationCodesClientInterface $inner,
    ) {}

    /**
     * The convenience the generator will not write: two objects in and two
     * objects out becomes a phone number, a code, and a yes or no.
     */
    public function isCodeValid(PhoneNumber $phone, string $code, ?int $clientId = null): bool
    {
        $response = $this->checkConfirmPhoneCode(new CheckConfirmPhoneCodeRequestContent(
            client_id: $clientId,
            phone: $phone,
            code: $code,
        ));

        // A declared 403 is a value on the envelope, not an exception, so
        // narrow to the 200 body before reading the vendor's success flag.
        if (!$response->body instanceof CheckConfirmPhoneCode200Content) {
            return false;
        }

        // The vendor signals success in `result_code`, not in the HTTP status.
        // The document declares the field `type: string`, so `'0'`, not `0`.
        return $response->body->result_code === '0';
    }

    /**
     * @return Envelope<AskConfirmPhone200Content|AskConfirmPhone403Content>
     */
    public function askConfirmPhone(AskConfirmPhoneRequestContent $body): Envelope
    {
        return $this->inner->askConfirmPhone($body);
    }

    /**
     * @return Envelope<CheckConfirmPhoneCode200Content|CheckConfirmPhoneCode403Content>
     */
    public function checkConfirmPhoneCode(CheckConfirmPhoneCodeRequestContent $body): Envelope
    {
        return $this->inner->checkConfirmPhoneCode($body);
    }
}
