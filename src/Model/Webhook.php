<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A webhook endpoint. status is enabled or disabled.
 */
final class Webhook extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $createdAt;

    public readonly string $status;

    public readonly string $endpoint;

    /** @var list<string> */
    public readonly array $events;

    public readonly ?string $signingSecret;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->createdAt = Data::string($data, 'created_at');
        $this->status = Data::string($data, 'status');
        $this->endpoint = Data::string($data, 'endpoint');
        $this->events = Data::stringList($data, 'events');
        $this->signingSecret = Data::nullableString($data, 'signing_secret');
    }
}
