<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A stored attachment of a sent email. downloadUrl is a signed link valid for 1 hour.
 */
final class EmailAttachment extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $filename;

    public readonly int $size;

    public readonly string $contentType;

    public readonly string $contentDisposition;

    public readonly ?string $contentId;

    public readonly string $downloadUrl;

    public readonly string $expiresAt;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->filename = Data::string($data, 'filename');
        $this->size = Data::int($data, 'size');
        $this->contentType = Data::string($data, 'content_type');
        $this->contentDisposition = Data::string($data, 'content_disposition');
        $this->contentId = Data::nullableString($data, 'content_id');
        $this->downloadUrl = Data::string($data, 'download_url');
        $this->expiresAt = Data::string($data, 'expires_at');
    }
}
