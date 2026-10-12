<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client returning queued responses (or throwing queued exceptions) and recording requests.
 */
final class MockHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<ResponseInterface|\Throwable> */
    private array $queue;

    /**
     * @param list<ResponseInterface|\Throwable> $queue
     */
    public function __construct(array $queue = [])
    {
        $this->queue = $queue;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue) ?? new Response(200, ['Content-Type' => 'application/json'], '{"object":"x","id":"1"}');
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    public function last(): RequestInterface
    {
        $last = end($this->requests);
        if (false === $last) {
            throw new \LogicException('No request was sent.');
        }

        return $last;
    }

    /**
     * @return mixed the decoded JSON body of the last request
     */
    public function lastJson(): mixed
    {
        return json_decode((string) $this->last()->getBody(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
