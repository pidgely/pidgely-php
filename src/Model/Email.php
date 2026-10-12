<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A sent or scheduled email. lastEvent is queued, scheduled, sent, delivered, delivery_delayed, bounced, complained, suppressed, failed or canceled.
 */
final class Email extends Model
{
    public readonly string $object;

    public readonly string $id;

    public readonly string $messageId;

    /** @var list<string> */
    public readonly array $to;

    /** @var list<string> */
    public readonly array $cc;

    /** @var list<string> */
    public readonly array $bcc;

    /** @var list<string> */
    public readonly array $replyTo;

    public readonly string $from;

    public readonly string $subject;

    public readonly ?string $html;

    public readonly ?string $text;

    public readonly string $createdAt;

    public readonly ?string $scheduledAt;

    public readonly string $lastEvent;

    /** @var list<Tag> */
    public readonly array $tags;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->id = Data::string($data, 'id');
        $this->messageId = Data::string($data, 'message_id');
        $this->to = Data::stringList($data, 'to');
        $this->cc = Data::stringList($data, 'cc');
        $this->bcc = Data::stringList($data, 'bcc');
        $this->replyTo = Data::stringList($data, 'reply_to');
        $this->from = Data::string($data, 'from');
        $this->subject = Data::string($data, 'subject');
        $this->html = Data::nullableString($data, 'html');
        $this->text = Data::nullableString($data, 'text');
        $this->createdAt = Data::string($data, 'created_at');
        $this->scheduledAt = Data::nullableString($data, 'scheduled_at');
        $this->lastEvent = Data::string($data, 'last_event');
        $this->tags = Data::models($data, 'tags', Tag::class);
    }
}
