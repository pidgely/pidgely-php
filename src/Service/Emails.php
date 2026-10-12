<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Email;
use Pidgely\Model\ResourceId;

/**
 * Send and manage emails.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Emails extends Service
{
    /** Stored attachments of sent emails. */
    public readonly EmailAttachments $attachments;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->attachments = new EmailAttachments($transport);
    }

    /**
     * POST /emails: send an email. Pass ['idempotency_key' => ...] as options to make it safe to retry.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function send(array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/emails', [], $params, $options));
    }

    /**
     * Alias of send().
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): ResourceId
    {
        return $this->send($params, $options);
    }

    /**
     * GET /emails/{id}: includes last_event.
     *
     * @param RequestOptions       $options
     */
    public function get(string $id, array $options = []): Email
    {
        return new Email($this->transport->request('GET', '/emails/'.self::id($id), [], null, $options));
    }

    /**
     * GET /emails
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Email>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/emails', self::page($params), $options, Email::class);
    }

    /**
     * PATCH /emails/{id}: reschedule a scheduled email (['id' => ..., 'scheduled_at' => ...]).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(array $params, array $options = []): ResourceId
    {
        $id = Transport::seg(self::require($params, 'id'));

        return new ResourceId($this->transport->request('PATCH', '/emails/'.$id, [], self::without($params, 'id'), $options));
    }

    /**
     * POST /emails/{id}/cancel: cancel a scheduled email.
     *
     * @param RequestOptions       $options
     */
    public function cancel(string $id, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/emails/'.self::id($id).'/cancel', [], null, $options));
    }
}
