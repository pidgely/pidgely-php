<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A broadcast. status is draft, scheduled, queued, sending, sent, canceled or failed; stats counts deliveries by status.
 */
final class Broadcast extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $name;

    public readonly ?string $segmentId;

    public readonly ?string $audienceId;

    public readonly string $from;

    public readonly string $subject;

    /** @var list<string> */
    public readonly array $replyTo;

    public readonly string $previewText;

    public readonly string $html;

    public readonly string $text;

    public readonly string $status;

    public readonly string $createdAt;

    public readonly ?string $scheduledAt;

    public readonly ?string $sentAt;

    public readonly int $recipients;

    /** @var array<string, mixed> */
    public readonly array $stats;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->name = Data::string($data, 'name');
        $this->segmentId = Data::nullableString($data, 'segment_id');
        $this->audienceId = Data::nullableString($data, 'audience_id');
        $this->from = Data::string($data, 'from');
        $this->subject = Data::string($data, 'subject');
        $this->replyTo = Data::stringList($data, 'reply_to');
        $this->previewText = Data::string($data, 'preview_text');
        $this->html = Data::string($data, 'html');
        $this->text = Data::string($data, 'text');
        $this->status = Data::string($data, 'status');
        $this->createdAt = Data::string($data, 'created_at');
        $this->scheduledAt = Data::nullableString($data, 'scheduled_at');
        $this->sentAt = Data::nullableString($data, 'sent_at');
        $this->recipients = Data::int($data, 'recipients');
        $this->stats = Data::map($data, 'stats');
    }
}
