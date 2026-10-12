<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A new API key. token (pdg_...) is only returned once.
 */
final class CreatedApiKey extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $token;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->token = Data::string($data, 'token');
    }
}
