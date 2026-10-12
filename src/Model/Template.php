<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A template (its current draft). status is draft or published.
 */
final class Template extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly ?string $currentVersionId;

    public readonly ?string $alias;

    public readonly string $name;

    public readonly string $createdAt;

    public readonly string $updatedAt;

    public readonly string $status;

    public readonly ?string $publishedAt;

    public readonly string $from;

    public readonly string $subject;

    /** @var list<string> */
    public readonly array $replyTo;

    public readonly string $html;

    public readonly string $text;

    /** @var list<TemplateVariable> */
    public readonly array $variables;

    public readonly bool $hasUnpublishedVersions;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->currentVersionId = Data::nullableString($data, 'current_version_id');
        $this->alias = Data::nullableString($data, 'alias');
        $this->name = Data::string($data, 'name');
        $this->createdAt = Data::string($data, 'created_at');
        $this->updatedAt = Data::string($data, 'updated_at');
        $this->status = Data::string($data, 'status');
        $this->publishedAt = Data::nullableString($data, 'published_at');
        $this->from = Data::string($data, 'from');
        $this->subject = Data::string($data, 'subject');
        $this->replyTo = Data::stringList($data, 'reply_to');
        $this->html = Data::string($data, 'html');
        $this->text = Data::string($data, 'text');
        $this->variables = Data::models($data, 'variables', TemplateVariable::class);
        $this->hasUnpublishedVersions = Data::bool($data, 'has_unpublished_versions');
    }
}
