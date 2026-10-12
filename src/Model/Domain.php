<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A sending domain. status is not_started, pending, verified or failed. records is set on create/get, empty in lists.
 */
final class Domain extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $name;

    public readonly string $status;

    public readonly string $createdAt;

    public readonly string $region;

    public readonly bool $openTracking;

    public readonly bool $clickTracking;

    public readonly string $tls;

    /** @var array<string, mixed> */
    public readonly array $capabilities;

    /** @var list<DomainRecord> */
    public readonly array $records;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->name = Data::string($data, 'name');
        $this->status = Data::string($data, 'status');
        $this->createdAt = Data::string($data, 'created_at');
        $this->region = Data::string($data, 'region');
        $this->openTracking = Data::bool($data, 'open_tracking');
        $this->clickTracking = Data::bool($data, 'click_tracking');
        $this->tls = Data::string($data, 'tls');
        $this->capabilities = Data::map($data, 'capabilities');
        $this->records = Data::models($data, 'records', DomainRecord::class);
    }
}
