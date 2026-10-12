<?php

declare(strict_types=1);

namespace Pidgely\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Pidgely\Client;
use Pidgely\Exception\PidgelyException;
use Pidgely\Model\Collection;
use Pidgely\Model\Domain;
use Pidgely\Model\Email;

final class ResourcesTest extends TestCase
{
    /**
     * @return iterable<string, array{\Closure(Client): mixed, string, string, array<string, string>, mixed}>
     */
    public static function endpoints(): iterable
    {
        // Emails
        yield 'emails.get' => [fn(Client $c) => $c->emails->get('e 1'), 'GET', '/emails/e%201', [], null];
        yield 'emails.list' => [fn(Client $c) => $c->emails->list(['limit' => 10, 'after' => 'cur']), 'GET', '/emails', ['limit' => '10', 'after' => 'cur'], null];
        yield 'emails.update' => [fn(Client $c) => $c->emails->update(['id' => 'e1', 'scheduled_at' => 'in 2 hours']), 'PATCH', '/emails/e1', [], ['scheduled_at' => 'in 2 hours']];
        yield 'emails.cancel' => [fn(Client $c) => $c->emails->cancel('e1'), 'POST', '/emails/e1/cancel', [], null];
        yield 'emails.attachments.list' => [fn(Client $c) => $c->emails->attachments->list(['email_id' => 'e1']), 'GET', '/emails/e1/attachments', [], null];
        yield 'emails.attachments.get' => [fn(Client $c) => $c->emails->attachments->get(['email_id' => 'e1', 'id' => 'a1']), 'GET', '/emails/e1/attachments/a1', [], null];
        yield 'batch.create' => [fn(Client $c) => $c->batch->create([['to' => 'a@b.c']]), 'POST', '/emails/batch', [], [['to' => 'a@b.c']]];
        // Domains
        yield 'domains.create' => [fn(Client $c) => $c->domains->create(['name' => 'acme.com', 'tls' => 'opportunistic']), 'POST', '/domains', [], ['name' => 'acme.com', 'tls' => 'opportunistic']];
        yield 'domains.get' => [fn(Client $c) => $c->domains->get('d1'), 'GET', '/domains/d1', [], null];
        yield 'domains.list' => [fn(Client $c) => $c->domains->list(['before' => 'x']), 'GET', '/domains', ['before' => 'x'], null];
        yield 'domains.update' => [fn(Client $c) => $c->domains->update(['id' => 'd1', 'tls' => 'enforced']), 'PATCH', '/domains/d1', [], ['tls' => 'enforced']];
        yield 'domains.remove' => [fn(Client $c) => $c->domains->remove('d1'), 'DELETE', '/domains/d1', [], null];
        yield 'domains.verify' => [fn(Client $c) => $c->domains->verify('d1'), 'POST', '/domains/d1/verify', [], null];
        // API keys
        yield 'apiKeys.create' => [fn(Client $c) => $c->apiKeys->create(['name' => 'Prod', 'permission' => 'sending_access', 'domain_id' => 'd1']), 'POST', '/api-keys', [], ['name' => 'Prod', 'permission' => 'sending_access', 'domain_id' => 'd1']];
        yield 'apiKeys.list' => [fn(Client $c) => $c->apiKeys->list(), 'GET', '/api-keys', [], null];
        yield 'apiKeys.update' => [fn(Client $c) => $c->apiKeys->update('k1', ['name' => 'New']), 'PATCH', '/api-keys/k1', [], ['name' => 'New']];
        yield 'apiKeys.remove' => [fn(Client $c) => $c->apiKeys->remove('k1'), 'DELETE', '/api-keys/k1', [], null];
        // Contacts
        yield 'contacts.create' => [fn(Client $c) => $c->contacts->create(['email' => 'ana@x.y', 'first_name' => 'Ana', 'segments' => [['id' => 's1']]]), 'POST', '/contacts', [], ['email' => 'ana@x.y', 'first_name' => 'Ana', 'segments' => [['id' => 's1']]]];
        yield 'contacts.create audience' => [fn(Client $c) => $c->contacts->create(['email' => 'ana@x.y', 'audience_id' => 'aud1']), 'POST', '/audiences/aud1/contacts', [], ['email' => 'ana@x.y']];
        yield 'contacts.list' => [fn(Client $c) => $c->contacts->list(['segment_id' => 's1', 'limit' => 100]), 'GET', '/contacts', ['limit' => '100', 'segment_id' => 's1'], null];
        yield 'contacts.list audience' => [fn(Client $c) => $c->contacts->list(['audience_id' => 'a1']), 'GET', '/contacts', ['audience_id' => 'a1'], null];
        yield 'contacts.get string' => [fn(Client $c) => $c->contacts->get('ana+x@x.y'), 'GET', '/contacts/ana+x@x.y', [], null];
        yield 'contacts.get email' => [fn(Client $c) => $c->contacts->get(['email' => 'ana@x.y']), 'GET', '/contacts/ana@x.y', [], null];
        yield 'contacts.get audience' => [fn(Client $c) => $c->contacts->get(['email' => 'ana@x.y', 'audience_id' => 'a1']), 'GET', '/audiences/a1/contacts/ana@x.y', [], null];
        yield 'contacts.update' => [fn(Client $c) => $c->contacts->update(['email' => 'ana@x.y', 'unsubscribed' => true]), 'PATCH', '/contacts/ana@x.y', [], ['unsubscribed' => true]];
        yield 'contacts.remove' => [fn(Client $c) => $c->contacts->remove(['id' => 'c1']), 'DELETE', '/contacts/c1', [], null];
        yield 'contacts.segments.list' => [fn(Client $c) => $c->contacts->segments->list(['email' => 'ana@x.y']), 'GET', '/contacts/ana@x.y/segments', [], null];
        yield 'contacts.segments.add' => [fn(Client $c) => $c->contacts->segments->add(['email' => 'ana@x.y', 'segment_id' => 's1']), 'POST', '/contacts/ana@x.y/segments/s1', [], null];
        yield 'contacts.segments.remove' => [fn(Client $c) => $c->contacts->segments->remove(['contact_id' => 'c1', 'segment_id' => 's1']), 'DELETE', '/contacts/c1/segments/s1', [], null];
        // Segments
        yield 'segments.create' => [fn(Client $c) => $c->segments->create(['name' => 'Pro']), 'POST', '/segments', [], ['name' => 'Pro']];
        yield 'segments.list' => [fn(Client $c) => $c->segments->list(), 'GET', '/segments', [], null];
        yield 'segments.get' => [fn(Client $c) => $c->segments->get('s1'), 'GET', '/segments/s1', [], null];
        yield 'segments.update' => [fn(Client $c) => $c->segments->update('s1', ['name' => 'Team']), 'PATCH', '/segments/s1', [], ['name' => 'Team']];
        yield 'segments.remove' => [fn(Client $c) => $c->segments->remove('s1'), 'DELETE', '/segments/s1', [], null];
        yield 'segments.listContacts' => [fn(Client $c) => $c->segments->listContacts('s1', ['limit' => 50]), 'GET', '/segments/s1/contacts', ['limit' => '50'], null];
        yield 'audiences alias' => [fn(Client $c) => $c->audiences->get('s1'), 'GET', '/segments/s1', [], null];
        // Broadcasts
        yield 'broadcasts.create' => [fn(Client $c) => $c->broadcasts->create(['segment_id' => 's1', 'from' => 'n@acme.com', 'subject' => 'News', 'html' => '<p>Hi</p>', 'send' => true]), 'POST', '/broadcasts', [], ['segment_id' => 's1', 'from' => 'n@acme.com', 'subject' => 'News', 'html' => '<p>Hi</p>', 'send' => true]];
        yield 'broadcasts.list' => [fn(Client $c) => $c->broadcasts->list(), 'GET', '/broadcasts', [], null];
        yield 'broadcasts.get' => [fn(Client $c) => $c->broadcasts->get('b1'), 'GET', '/broadcasts/b1', [], null];
        yield 'broadcasts.update' => [fn(Client $c) => $c->broadcasts->update('b1', ['subject' => 'New']), 'PATCH', '/broadcasts/b1', [], ['subject' => 'New']];
        yield 'broadcasts.remove' => [fn(Client $c) => $c->broadcasts->remove('b1'), 'DELETE', '/broadcasts/b1', [], null];
        yield 'broadcasts.send now' => [fn(Client $c) => $c->broadcasts->send('b1'), 'POST', '/broadcasts/b1/send', [], '{}'];
        yield 'broadcasts.send later' => [fn(Client $c) => $c->broadcasts->send('b1', ['scheduled_at' => 'in 1 hour']), 'POST', '/broadcasts/b1/send', [], ['scheduled_at' => 'in 1 hour']];
        yield 'broadcasts.cancel' => [fn(Client $c) => $c->broadcasts->cancel('b1'), 'POST', '/broadcasts/b1/cancel', [], null];
        yield 'broadcasts.duplicate' => [fn(Client $c) => $c->broadcasts->duplicate('b1'), 'POST', '/broadcasts/b1/duplicate', [], null];
        // Templates
        yield 'templates.create' => [fn(Client $c) => $c->templates->create(['name' => 'Welcome', 'variables' => [['key' => 'N', 'type' => 'number', 'fallback_value' => 14]]]), 'POST', '/templates', [], ['name' => 'Welcome', 'variables' => [['key' => 'N', 'type' => 'number', 'fallback_value' => 14]]]];
        yield 'templates.list' => [fn(Client $c) => $c->templates->list(), 'GET', '/templates', [], null];
        yield 'templates.get' => [fn(Client $c) => $c->templates->get('welcome'), 'GET', '/templates/welcome', [], null];
        yield 'templates.update' => [fn(Client $c) => $c->templates->update('welcome', ['subject' => 'New']), 'PATCH', '/templates/welcome', [], ['subject' => 'New']];
        yield 'templates.remove' => [fn(Client $c) => $c->templates->remove('welcome'), 'DELETE', '/templates/welcome', [], null];
        yield 'templates.publish' => [fn(Client $c) => $c->templates->publish('welcome'), 'POST', '/templates/welcome/publish', [], null];
        yield 'templates.duplicate' => [fn(Client $c) => $c->templates->duplicate('welcome'), 'POST', '/templates/welcome/duplicate', [], null];
        // Webhooks
        yield 'webhooks.create' => [fn(Client $c) => $c->webhooks->create(['endpoint' => 'https://acme.com/wh', 'events' => ['email.delivered']]), 'POST', '/webhooks', [], ['endpoint' => 'https://acme.com/wh', 'events' => ['email.delivered']]];
        yield 'webhooks.list' => [fn(Client $c) => $c->webhooks->list(), 'GET', '/webhooks', [], null];
        yield 'webhooks.get' => [fn(Client $c) => $c->webhooks->get('w1'), 'GET', '/webhooks/w1', [], null];
        yield 'webhooks.update' => [fn(Client $c) => $c->webhooks->update('w1', ['status' => 'disabled']), 'PATCH', '/webhooks/w1', [], ['status' => 'disabled']];
        yield 'webhooks.remove' => [fn(Client $c) => $c->webhooks->remove('w1'), 'DELETE', '/webhooks/w1', [], null];
        yield 'webhooks.rotate' => [fn(Client $c) => $c->webhooks->rotateSigningSecret('w1'), 'POST', '/webhooks/w1/signing-secret/rotate', [], null];
        // Suppressions
        yield 'suppressions.add' => [fn(Client $c) => $c->suppressions->add(['email' => 'x@y.z']), 'POST', '/suppressions', [], ['email' => 'x@y.z']];
        yield 'suppressions.create' => [fn(Client $c) => $c->suppressions->create(['email' => 'x@y.z']), 'POST', '/suppressions', [], ['email' => 'x@y.z']];
        yield 'suppressions.list' => [fn(Client $c) => $c->suppressions->list(['origin' => 'bounce']), 'GET', '/suppressions', ['origin' => 'bounce'], null];
        yield 'suppressions.get' => [fn(Client $c) => $c->suppressions->get('x@y.z'), 'GET', '/suppressions/x@y.z', [], null];
        yield 'suppressions.remove' => [fn(Client $c) => $c->suppressions->remove('x@y.z'), 'DELETE', '/suppressions/x@y.z', [], null];
        yield 'suppressions.batch.add' => [fn(Client $c) => $c->suppressions->batch->add(['emails' => ['a@x.y']]), 'POST', '/suppressions/batch/add', [], ['emails' => ['a@x.y']]];
        yield 'suppressions.batch.remove' => [fn(Client $c) => $c->suppressions->batch->remove(['ids' => ['i1']]), 'POST', '/suppressions/batch/remove', [], ['ids' => ['i1']]];
    }

    /**
     * @param \Closure(Client): mixed $call
     * @param array<string, string>   $query
     */
    #[DataProvider('endpoints')]
    public function testEndpoint(\Closure $call, string $method, string $path, array $query, mixed $body): void
    {
        $client = $this->client();
        $call($client);
        $request = $this->http->last();
        self::assertSame($method, $request->getMethod());
        self::assertSame($path, $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $actualQuery);
        self::assertSame($query, $actualQuery);
        if (null === $body) {
            self::assertSame('', (string) $request->getBody());
            self::assertFalse($request->hasHeader('Content-Type'));
        } elseif (\is_string($body)) {
            self::assertSame($body, (string) $request->getBody());
        } else {
            self::assertSame($body, $this->http->lastJson());
            self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        }
    }

    public function testSendEmail(): void
    {
        $client = $this->client([self::json(200, ['id' => 'e1'])]);
        $sent = $client->emails->send([
            'from' => 'Acme <hello@acme.com>',
            'to' => 'ana@example.org',
            'subject' => 'Welcome to Acme',
            'html' => '<p>Your account is ready.</p>',
            'attachments' => [\Pidgely\Attachment::fromContent('hello', 'a.txt', 'text/plain'), \Pidgely\Attachment::fromUrl('https://acme.com/logo.png', 'logo.png', 'logo')],
        ], ['idempotency_key' => 'welcome-ana']);
        self::assertSame('e1', $sent->id);
        self::assertSame('e1', $sent['id']);
        $request = $this->http->last();
        self::assertSame('POST', $request->getMethod());
        self::assertSame(self::BASE.'/emails', (string) $request->getUri());
        self::assertSame('Bearer '.self::API_KEY, $request->getHeaderLine('Authorization'));
        self::assertSame('pidgely-php/'.Client::VERSION, $request->getHeaderLine('User-Agent'));
        self::assertSame('welcome-ana', $request->getHeaderLine('Idempotency-Key'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        $json = $this->http->lastJson();
        self::assertIsArray($json);
        self::assertSame([
            ['filename' => 'a.txt', 'content' => 'aGVsbG8=', 'content_type' => 'text/plain'],
            ['path' => 'https://acme.com/logo.png', 'filename' => 'logo.png', 'content_id' => 'logo'],
        ], $json['attachments']);
        self::assertStringContainsString('"html":"<p>Your account is ready.</p>"', (string) $request->getBody());
    }

    public function testEmailsCreateAliasAndBatch(): void
    {
        $client = $this->client([self::json(200, ['id' => 'e2']), self::json(200, ['data' => [['id' => 'a']], 'errors' => [['index' => 1, 'message' => 'bad']]])]);
        self::assertSame('e2', $client->emails->create(['to' => 'a@b.c'])->id);
        $result = $client->batch->send([['to' => 'a@b.c'], ['to' => 'nope']], ['batch_validation' => 'permissive', 'idempotency_key' => 'b1']);
        self::assertSame('a', $result->data[0]->id);
        self::assertSame([['index' => 1, 'message' => 'bad']], $result->errors);
        self::assertSame('permissive', $this->http->last()->getHeaderLine('x-batch-validation'));
        self::assertSame('b1', $this->http->last()->getHeaderLine('Idempotency-Key'));
    }

    public function testTypedResponses(): void
    {
        $client = $this->client([
            self::json(200, ['object' => 'email', 'id' => 'e1', 'to' => ['a@b.c'], 'from' => 'x@y.z', 'html' => null, 'created_at' => '2026-10-12 16:44:41.000000+00', 'scheduled_at' => null, 'last_event' => 'delivered', 'tags' => [['name' => 'n', 'value' => 'v']], 'future_field' => 1]),
            self::json(200, ['object' => 'list', 'has_more' => true, 'data' => [['id' => 'd1', 'name' => 'acme.com', 'status' => 'pending', 'capabilities' => ['sending' => 'enabled'], 'records' => [['record' => 'DKIM', 'type' => 'TXT', 'value' => 'p=x', 'priority' => 10]]]]]),
            self::json(200, ['object' => 'broadcast', 'id' => 'b1', 'stats' => ['delivered' => 10], 'recipients' => 11]),
            self::json(200, ['object' => 'template', 'id' => 't1', 'alias' => 'welcome', 'variables' => [['key' => 'N', 'type' => 'number', 'fallback_value' => 14]], 'has_unpublished_versions' => true]),
            self::json(200, ['object' => 'api_key', 'id' => 'k1', 'token' => 'pdg_new']),
            self::json(200, ['object' => 'webhook', 'id' => 'w1', 'signing_secret' => 'whsec_abc']),
            self::json(200, ['data' => [['object' => 'suppression', 'id' => 's', 'deleted' => true]]]),
            self::json(200, ['object' => 'contact', 'id' => 'c', 'email' => 'a@b.c', 'unsubscribed' => false, 'properties' => ['plan' => ['value' => 'pro', 'type' => 'string']]]),
        ]);
        $email = $client->emails->get('e1');
        self::assertInstanceOf(Email::class, $email);
        self::assertSame(['a@b.c'], $email->to);
        self::assertNull($email->html);
        self::assertSame('delivered', $email->lastEvent);
        self::assertSame('v', $email->tags[0]->value);
        self::assertSame(1, $email['future_field']);
        self::assertTrue(isset($email['id']));
        self::assertSame('e1', $email->toArray()['id']);
        self::assertSame('{"object":"email"', substr((string) json_encode($email), 0, 17));

        $domains = $client->domains->list();
        self::assertInstanceOf(Collection::class, $domains);
        self::assertTrue($domains->hasMore);
        self::assertCount(1, $domains);
        foreach ($domains as $domain) {
            self::assertInstanceOf(Domain::class, $domain);
            self::assertSame(10, $domain->records[0]->priority);
            self::assertSame('enabled', $domain->capabilities['sending']);
        }
        self::assertSame(['delivered' => 10], $client->broadcasts->get('b1')->stats);
        $template = $client->templates->get('welcome');
        self::assertSame(14, $template->variables[0]->fallbackValue);
        self::assertTrue($template->hasUnpublishedVersions);
        self::assertSame('pdg_new', $client->apiKeys->create(['name' => 'x'])->token);
        self::assertSame('whsec_abc', $client->webhooks->create(['endpoint' => 'https://x', 'events' => ['email.sent']])->signingSecret);
        self::assertTrue($client->suppressions->batch->remove(['emails' => ['a@b.c']])->data[0]->deleted);
        self::assertSame(['plan' => ['value' => 'pro', 'type' => 'string']], $client->contacts->get('c')->properties);
    }

    public function testModelsAreReadOnly(): void
    {
        $email = new Email(['id' => 'e1']);
        $this->expectException(\LogicException::class);
        $email['id'] = 'x';
    }

    public function testModelsUnsetIsRejected(): void
    {
        $email = new Email(['id' => 'e1']);
        $this->expectException(\LogicException::class);
        unset($email['id']);
    }

    /**
     * @return iterable<string, array{\Closure(Client): mixed}>
     */
    public static function missing(): iterable
    {
        yield 'emails.get' => [fn(Client $c) => $c->emails->get('')];
        yield 'emails.update' => [fn(Client $c) => $c->emails->update(['scheduled_at' => 'x'])];
        yield 'attachments.list' => [fn(Client $c) => $c->emails->attachments->list([])];
        yield 'attachments.get' => [fn(Client $c) => $c->emails->attachments->get(['email_id' => 'e'])];
        yield 'domains.update' => [fn(Client $c) => $c->domains->update(['tls' => 'enforced'])];
        yield 'contacts.get' => [fn(Client $c) => $c->contacts->get([])];
        yield 'contacts.get empty' => [fn(Client $c) => $c->contacts->get('')];
        yield 'contacts.update' => [fn(Client $c) => $c->contacts->update(['first_name' => 'x'])];
        yield 'contacts.segments.add' => [fn(Client $c) => $c->contacts->segments->add(['email' => 'a@b.c'])];
        yield 'templates.publish' => [fn(Client $c) => $c->templates->publish('')];
    }

    /**
     * @param \Closure(Client): mixed $call
     */
    #[DataProvider('missing')]
    public function testMissingRequired(\Closure $call): void
    {
        $client = $this->client();
        try {
            $call($client);
            self::fail('Expected an exception');
        } catch (PidgelyException $e) {
            self::assertSame('missing_required_field', $e->getErrorName());
            self::assertNull($e->getStatusCode());
        }
        self::assertSame([], $this->http->requests);
    }
}
