<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * An address Pidgely will not send to. origin is bounce, complaint, manual or unsubscribe.
 */
final class Suppression extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $email;

    public readonly string $origin;

    public readonly ?string $sourceId;

    public readonly string $createdAt;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->email = Data::string($data, 'email');
        $this->origin = Data::string($data, 'origin');
        $this->sourceId = Data::nullableString($data, 'source_id');
        $this->createdAt = Data::string($data, 'created_at');
    }
}
