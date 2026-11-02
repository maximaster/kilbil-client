<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 mock that returns a fixed response and records the last request.
 *
 * Lives in its own file so speconaut's step-stub appender does not splice
 * new step methods into it by mistake (it appends to whichever class is
 * last in the target file).
 */
final class RecordingClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ResponseInterface $response) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
