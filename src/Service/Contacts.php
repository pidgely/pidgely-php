<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Contact;
use Pidgely\Model\ContactDeleted;
use Pidgely\Model\ResourceId;

/**
 * Contacts, addressed by id or email.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Contacts extends Service
{
    /** Segment memberships of a contact. */
    public readonly ContactSegments $segments;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->segments = new ContactSegments($transport);
    }

    /**
     * @param string|array<string, mixed> $contact an id, an email, ['id' => ...] or ['email' => ...] (plus a legacy audience_id)
     */
    private static function path(string|array $contact): string
    {
        if (\is_string($contact)) {
            return '/contacts/'.self::id($contact, 'id or email');
        }
        $key = Transport::seg(self::require($contact, 'id', 'email'));
        $audience = $contact['audience_id'] ?? null;

        return (\is_string($audience) && '' !== $audience ? '/audiences/'.Transport::seg($audience).'/contacts' : '/contacts').'/'.$key;
    }

    /**
     * POST /contacts: creates the contact or updates the one with the same email. A legacy audience_id uses /audiences/{id}/contacts.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): ResourceId
    {
        $audience = $params['audience_id'] ?? null;
        $path = \is_string($audience) && '' !== $audience ? '/audiences/'.Transport::seg($audience).'/contacts' : '/contacts';

        return new ResourceId($this->transport->request('POST', $path, [], self::without($params, 'audience_id'), $options));
    }

    /**
     * GET /contacts, optionally filtered by segment_id.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Contact>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/contacts', self::page($params, 'segment_id', 'audience_id'), $options, Contact::class);
    }

    /**
     * GET /contacts/{id_or_email}: pass an id, an email, ['id' => ...] or ['email' => ...].
     *
     * @param string|array<string, mixed> $contact
     * @param RequestOptions              $options
     */
    public function get(string|array $contact, array $options = []): Contact
    {
        return new Contact($this->transport->request('GET', self::path($contact), [], null, $options));
    }

    /**
     * PATCH /contacts/{id_or_email}: unsubscribed stays in sync with the suppression list.
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', self::path($params), [], self::without($params, 'id', 'email', 'audience_id'), $options));
    }

    /**
     * DELETE /contacts/{id_or_email}
     *
     * @param string|array<string, mixed> $contact
     * @param RequestOptions              $options
     */
    public function remove(string|array $contact, array $options = []): ContactDeleted
    {
        return new ContactDeleted($this->transport->request('DELETE', self::path($contact), [], null, $options));
    }
}
