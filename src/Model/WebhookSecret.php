<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Returned when a webhook is created or its secret rotated. Store signingSecret (whsec_...) to verify deliveries.
 */
final class WebhookSecret extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $signingSecret;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->signingSecret = Data::string($data, 'signing_secret');
    }
}
