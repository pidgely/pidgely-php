<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;
use Pidgely\Model\Suppression;

/**
 * The suppression list.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Suppressions extends Service
{
    /** Bulk changes (up to 100 addresses). */
    public readonly SuppressionsBatch $batch;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->batch = new SuppressionsBatch($transport);
    }

    /**
     * POST /suppressions: stop sending to an address.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function add(array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/suppressions', [], $params, $options));
    }

    /**
     * Alias of add().
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): ResourceId
    {
        return $this->add($params, $options);
    }

    /**
     * GET /suppressions, optionally filtered by origin.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Suppression>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/suppressions', self::page($params, 'origin'), $options, Suppression::class);
    }

    /**
     * GET /suppressions/{id_or_email}
     *
     * @param RequestOptions       $options
     */
    public function get(string $idOrEmail, array $options = []): Suppression
    {
        return new Suppression($this->transport->request('GET', '/suppressions/'.self::id($idOrEmail), [], null, $options));
    }

    /**
     * DELETE /suppressions/{id_or_email}: allow sending to the address again.
     *
     * @param RequestOptions       $options
     */
    public function remove(string $idOrEmail, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/suppressions/'.self::id($idOrEmail), [], null, $options));
    }
}
