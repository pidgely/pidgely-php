<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\BatchResult;

/**
 * Send up to 100 emails in one request.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Batch extends Service
{
    /**
     * POST /emails/batch. Options also take batch_validation (strict or permissive).
     *
     * @param list<array<string, mixed>> $emails
     * @param RequestOptions             $options
     */
    public function send(array $emails, array $options = []): BatchResult
    {
        $headers = isset($options['batch_validation']) ? ['x-batch-validation' => $options['batch_validation']] : [];

        return new BatchResult($this->transport->request('POST', '/emails/batch', [], $emails, $options, $headers));
    }

    /**
     * Alias of send().
     *
     * @param list<array<string, mixed>> $emails
     * @param RequestOptions             $options
     */
    public function create(array $emails, array $options = []): BatchResult
    {
        return $this->send($emails, $options);
    }
}
