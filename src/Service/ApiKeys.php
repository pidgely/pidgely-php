<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\ApiKey;
use Pidgely\Model\Collection;
use Pidgely\Model\CreatedApiKey;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;

/**
 * API keys.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class ApiKeys extends Service
{
    /**
     * POST /api-keys: the token is only returned once.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): CreatedApiKey
    {
        return new CreatedApiKey($this->transport->request('POST', '/api-keys', [], $params, $options));
    }

    /**
     * GET /api-keys
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<ApiKey>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/api-keys', self::page($params), $options, ApiKey::class);
    }

    /**
     * PATCH /api-keys/{id}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(string $id, array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', '/api-keys/'.self::id($id), [], $params, $options));
    }

    /**
     * DELETE /api-keys/{id}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $id, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/api-keys/'.self::id($id), [], null, $options));
    }
}
