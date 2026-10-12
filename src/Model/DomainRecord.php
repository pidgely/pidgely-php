<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A DNS record to publish (DKIM, SPF or DMARC).
 */
final class DomainRecord extends Model
{
    public readonly string $record;

    public readonly string $name;

    public readonly string $type;

    public readonly string $ttl;

    public readonly string $status;

    public readonly string $value;

    public readonly ?int $priority;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->record = Data::string($data, 'record');
        $this->name = Data::string($data, 'name');
        $this->type = Data::string($data, 'type');
        $this->ttl = Data::string($data, 'ttl');
        $this->status = Data::string($data, 'status');
        $this->value = Data::string($data, 'value');
        $this->priority = Data::nullableInt($data, 'priority');
    }
}
