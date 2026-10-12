<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\EmailAttachment;

/**
 * Attachments of sent emails.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class EmailAttachments extends Service
{
    /**
     * GET /emails/{email_id}/attachments (['email_id' => ...]).
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<EmailAttachment>
     */
    public function list(array $params, array $options = []): Collection
    {
        $emailId = Transport::seg(self::require($params, 'email_id'));

        return $this->collection('/emails/'.$emailId.'/attachments', self::page($params), $options, EmailAttachment::class);
    }

    /**
     * GET /emails/{email_id}/attachments/{id}: includes a signed download_url valid for 1 hour.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function get(array $params, array $options = []): EmailAttachment
    {
        $emailId = Transport::seg(self::require($params, 'email_id'));
        $id = Transport::seg(self::require($params, 'id'));

        return new EmailAttachment($this->transport->request('GET', '/emails/'.$emailId.'/attachments/'.$id, [], null, $options));
    }
}
