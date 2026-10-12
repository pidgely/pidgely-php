<?php

declare(strict_types=1);

namespace Pidgely\Exception;

/**
 * Base class of every API, network and configuration error raised by the SDK.
 *
 * getErrorName() is the API's machine-readable name (validation_error, not_found,
 * rate_limit_exceeded...). getStatusCode() is the HTTP status, or null when no response
 * was received. The API key never appears in an exception.
 */
class PidgelyException extends \RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $message,
        private readonly string $errorName = 'application_error',
        private readonly ?int $statusCode = null,
        private readonly array $headers = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function getErrorName(): string
    {
        return $this->errorName;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Response headers, lower-cased. Empty when no response was received.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
