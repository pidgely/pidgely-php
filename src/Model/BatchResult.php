<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * Result of a batch send. errors is set in permissive mode: list of {index, message}.
 */
final class BatchResult extends Model
{
    /** @var list<ResourceId> */
    public readonly array $data;

    /** @var list<array<string, mixed>> */
    public readonly array $errors;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->data = Data::models($data, 'data', ResourceId::class);
        $this->errors = Data::objects($data, 'errors');
    }
}
