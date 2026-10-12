<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A verified webhook delivery (Resend format).
 */
final class WebhookEvent extends Model
{
    public readonly string $type;

    public readonly string $createdAt;

    /** @var array<string, mixed> */
    public readonly array $data;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->type = Data::string($data, 'type');
        $this->createdAt = Data::string($data, 'created_at');
        $this->data = Data::map($data, 'data');
    }
}
