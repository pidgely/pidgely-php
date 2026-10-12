<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;

/**
 * Bulk suppression changes (up to 100 addresses).
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class SuppressionsBatch extends Service
{
    /**
     * POST /suppressions/batch/add (['emails' => [...]]).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<ResourceId>
     */
    public function add(array $params, array $options = []): Collection
    {
        return new Collection($this->transport->request('POST', '/suppressions/batch/add', [], $params, $options), ResourceId::class);
    }

    /**
     * POST /suppressions/batch/remove (['emails' => [...]] and/or ['ids' => [...]]).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Deleted>
     */
    public function remove(array $params, array $options = []): Collection
    {
        return new Collection($this->transport->request('POST', '/suppressions/batch/remove', [], $params, $options), Deleted::class);
    }
}
