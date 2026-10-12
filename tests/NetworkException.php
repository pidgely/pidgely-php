<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

final class NetworkException extends \RuntimeException implements NetworkExceptionInterface
{
    public function getRequest(): RequestInterface
    {
        return new \GuzzleHttp\Psr7\Request('GET', 'https://example.test');
    }
}
