<p align="center">
  <a href="https://pidgely.com"><img src="https://raw.githubusercontent.com/pidgely/.github/main/assets/banner.png" alt="Pidgely — Email API for developers" width="100%"></a>
</p>

<p align="center">
  <a href="https://github.com/pidgely/pidgely-php/actions/workflows/ci.yml"><img src="https://github.com/pidgely/pidgely-php/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/pidgely/pidgely-php"><img src="https://img.shields.io/packagist/v/pidgely/pidgely-php?color=E2793F" alt="Packagist"></a>
  <img src="https://img.shields.io/badge/php-%E2%89%A58.1-777BB4?logo=php&logoColor=white" alt="PHP ≥ 8.1">
  <img src="https://img.shields.io/badge/PHPStan-level%20max-success" alt="PHPStan level max">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue" alt="MIT"></a>
</p>

# pidgely-php

The official PHP SDK for [Pidgely](https://pidgely.com), the Resend-compatible email API.

- **Drop-in for Resend.** The same `Pidgely::client($key)->emails->send([...])` shape as `resend-php`. Migrating means changing a class name and a key.
- **PSR-18 / PSR-17.** Works with any HTTP client; Guzzle is used when installed, otherwise one is discovered with `php-http/discovery`.
- **Typed responses.** Read-only DTOs (`$email->lastEvent`) that also keep the raw response (`$email['last_event']`, `toArray()`). Checked with PHPStan at level max.
- **Built for production.** Idempotency keys, automatic retries with backoff that honour `Retry-After`, timeouts.
- **Webhook verification.** Standard Webhooks (Svix) signatures, checked in constant time with timestamp tolerance.

Full documentation lives at **[docs.pidgely.com](https://docs.pidgely.com)**.

## Install

```sh
composer require pidgely/pidgely-php guzzlehttp/guzzle
```

Requires PHP 8.1 or later. Guzzle is the suggested HTTP client; any PSR-18 client works (`symfony/http-client`, `php-http/curl-client`...).

## Quickstart

Create an API key in the [dashboard](https://app.pidgely.com), verify a sending domain, then:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$pidgely = Pidgely::client(getenv('PIDGELY_API_KEY'));

$email = $pidgely->emails->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => 'ana@example.org',
    'subject' => 'Welcome to Acme',
    'html' => '<p>Your account is ready.</p>',
]);

echo $email->id;
```

### Configuration

```php
$pidgely = Pidgely::client(getenv('PIDGELY_API_KEY'), [
    'base_url' => 'https://api.pidgely.com', // or PIDGELY_BASE_URL
    'timeout' => 30,                         // seconds per attempt
    'max_retries' => 2,                      // automatic retries, see below
    'user_agent' => 'my-app/1.2.3',          // appended to pidgely-php/<version>
    'headers' => ['X-Team' => 'growth'],     // sent with every request
    'http_client' => $psr18Client,           // optional; plus request_factory / stream_factory
]);
```

| Environment variable | Used for |
|---|---|
| `PIDGELY_API_KEY` | API key when none is given |
| `PIDGELY_BASE_URL` | Base URL when none is given (default `https://api.pidgely.com`) |

The API key never appears in `var_dump()`, `print_r()`, `var_export()` or exception messages, and it is marked `#[\SensitiveParameter]` so it is redacted from stack traces (PHP 8.2+). Clients refuse to be serialized.

### Errors

Every failure throws a `Pidgely\Exception\PidgelyException`:

```php
use Pidgely\Exception\PidgelyException;
use Pidgely\Exception\RateLimitException;
use Pidgely\Exception\ValidationException;

try {
    $pidgely->emails->send([...]);
} catch (ValidationException $e) {           // 400 / 422
    echo $e->getStatusCode(), ' ', $e->getErrorName(), ': ', $e->getMessage();
} catch (RateLimitException $e) {            // 429
    $retryAfter = $e->getHeaders()['retry-after'] ?? null;
} catch (PidgelyException $e) {
    // everything else
}
```

| Exception | When |
|---|---|
| `ValidationException` | 400, 422 |
| `AuthenticationException` | 401 |
| `PermissionDeniedException` | 403 |
| `NotFoundException` | 404 |
| `ConflictException` | 409 (e.g. idempotency key reused with another body) |
| `RateLimitException` | 429 |
| `InternalServerException` | 5xx |
| `ApiConnectionException` / `ApiTimeoutException` | no response (`getStatusCode()` is `null`) |
| `MissingApiKeyException` | no API key configured |

`getErrorName()` is machine-readable: `validation_error`, `not_found`, `invalid_api_key`, `rate_limit_exceeded`, `daily_quota_exceeded`, ...

Every method takes an optional last `$options` array: `idempotency_key`, `headers`, `timeout` (honoured per request with Guzzle) and `max_retries`.

### Idempotency

```php
$pidgely->emails->send(
    ['from' => 'Acme <billing@acme.com>', 'to' => 'ana@example.com', 'subject' => 'Receipt #1042', 'html' => $html],
    ['idempotency_key' => 'receipt-1042'],
);
```

The key is sent as `Idempotency-Key`. For 24 hours the API replays the stored response for the same key (the reply carries the header `idempotent-replayed: true`) and answers `409` when the body differs.

### Retries

Failed requests are retried up to `max_retries` times (default 2) with exponential backoff and jitter (0.5 s, 1 s, 2 s and so on, capped at 8 s), or after the delay given in `Retry-After`:

| Failure | Retried when |
|---|---|
| `408`, `429`, `500`, `502`, `503`, `504`, network error, timeout | `GET`, `PATCH`, `DELETE`, or a `POST` that has an `idempotency_key` |

**A `POST` without an idempotency key is never retried**, whatever the failure, because the email might already have been accepted. Quota errors (`daily_quota_exceeded`, `monthly_quota_exceeded`) are never retried, and a `Retry-After` longer than 60 seconds throws instead of waiting.

## Resources

Parameters are arrays with the API's snake_case keys. Responses are typed objects with camelCase properties; list methods take `['limit', 'after', 'before']` (cursor pagination) and return an iterable, countable `Collection` with `hasMore` and `data`.

### Emails

```php
use Pidgely\Attachment;

$email = $pidgely->emails->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => ['ana@example.com'],
    'cc' => 'team@acme.com',
    'reply_to' => 'support@acme.com',
    'subject' => 'Your invoice',
    'html' => '<p>Invoice attached.</p>', // `text` is generated when omitted
    'attachments' => [
        Attachment::fromFile('/path/to/invoice.pdf'),                        // ['filename' => ..., 'content' => base64]
        Attachment::fromUrl('https://acme.com/logo.png', 'logo.png', 'logo'), // inline: <img src="cid:logo">
    ],
    'tags' => [['name' => 'category', 'value' => 'billing']],
    'headers' => ['X-Entity-Ref-ID' => 'inv_1042'],
    'scheduled_at' => 'tomorrow at 9am', // ISO 8601 or natural language, up to 30 days ahead
]);

// Send a published template
$pidgely->emails->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => 'ana@example.com',
    'template' => ['id' => 'welcome', 'variables' => ['NAME' => 'Ana', 'TRIAL_DAYS' => 14]],
]);

$pidgely->emails->get('4ef9a417-02e9-4d39-ad75-9611e0fcc33c'); // ->lastEvent
$pidgely->emails->list(['limit' => 20]);
$pidgely->emails->update(['id' => $id, 'scheduled_at' => 'in 2 hours']); // reschedule
$pidgely->emails->cancel($id);                                            // cancel a scheduled email
$pidgely->emails->attachments->list(['email_id' => $id]);
$pidgely->emails->attachments->get(['email_id' => $id, 'id' => $attachmentId]); // ->downloadUrl, valid for 1 hour
```

### Batch

```php
$result = $pidgely->batch->send([
    ['from' => 'Acme <hello@acme.com>', 'to' => 'ana@example.com', 'subject' => 'Hi Ana', 'html' => '<p>Hi</p>'],
    ['from' => 'Acme <hello@acme.com>', 'to' => 'bob@example.com', 'subject' => 'Hi Bob', 'html' => '<p>Hi</p>'],
], ['idempotency_key' => 'digest-2026-10-12', 'batch_validation' => 'permissive']);
// $result->data → ids; with 'permissive', $result->errors → [['index' => ..., 'message' => ...]]
```

Up to 100 emails per request. `strict` (the default) sends all of them or none. `permissive` sends the valid ones and reports the invalid ones.

### Domains

```php
$domain = $pidgely->domains->create(['name' => 'acme.com', 'custom_return_path' => 'bounces', 'tls' => 'opportunistic']);
foreach ($domain->records as $record) { // DKIM TXT, return-path MX + SPF TXT, DMARC
    echo $record->type, ' ', $record->name, ' ', $record->value, PHP_EOL;
}
$pidgely->domains->verify($domain->id); // check DNS now
$pidgely->domains->get($domain->id);    // ->status: not_started | pending | verified | failed
$pidgely->domains->list();
$pidgely->domains->update(['id' => $domain->id, 'tls' => 'enforced']);
$pidgely->domains->remove($domain->id);
```

### API keys

```php
$key = $pidgely->apiKeys->create(['name' => 'Production', 'permission' => 'sending_access', 'domain_id' => $domainId]);
echo $key->token; // pdg_…, shown only once
$pidgely->apiKeys->list();
$pidgely->apiKeys->update($key->id, ['name' => 'Production (web)']);
$pidgely->apiKeys->remove($key->id);
```

### Contacts

```php
$pidgely->contacts->create([
    'email' => 'ana@example.com',
    'first_name' => 'Ana',
    'last_name' => 'Lima',
    'properties' => ['plan' => 'pro', 'seats' => 5],
    'segments' => [['id' => $segmentId]],
]);
$pidgely->contacts->list(['segment_id' => $segmentId, 'limit' => 100]);
$pidgely->contacts->get(['email' => 'ana@example.com']); // or get('ana@example.com') / get(['id' => ...])
$pidgely->contacts->update(['email' => 'ana@example.com', 'unsubscribed' => true]); // kept in sync with the suppression list
$pidgely->contacts->remove(['email' => 'ana@example.com']);

// Upsert up to 1,000 contacts; invalid rows are reported with their index instead of failing the request
$result = $pidgely->contacts->batch([
    'contacts' => [
        ['email' => 'ana@example.com', 'first_name' => 'Ana'],
        ['email' => 'bruno@example.com', 'properties' => ['plan' => 'team']],
    ],
    'segment_id' => $segmentId, // optional: added to every row
]);
// $result->data → [{index, id}], $result->errors → [['index' => ..., 'message' => ...]]

$pidgely->contacts->segments->list(['email' => 'ana@example.com']);
$pidgely->contacts->segments->add(['email' => 'ana@example.com', 'segment_id' => $segmentId]);
$pidgely->contacts->segments->remove(['contact_id' => $contactId, 'segment_id' => $segmentId]);
```

### Segments (and legacy audiences)

```php
$segment = $pidgely->segments->create(['name' => 'Pro customers']);
$pidgely->segments->list();
$pidgely->segments->get($segment->id);
$pidgely->segments->update($segment->id, ['name' => 'Pro & Team customers']);
$pidgely->segments->listContacts($segment->id, ['limit' => 50]);
$pidgely->segments->remove($segment->id);
```

`$pidgely->audiences` is a deprecated alias of `$pidgely->segments`, kept for Resend compatibility. `contacts->create/list/get/update/remove` also take a legacy `audience_id`.

### Broadcasts

```php
$broadcast = $pidgely->broadcasts->create([
    'segment_id' => $segmentId,
    'from' => 'Acme News <news@acme.com>',
    'subject' => 'What’s new in October',
    'preview_text' => 'Three features you asked for',
    'html' => '<p>Hi {{{FIRST_NAME|there}}},</p>…<a href="{{{PIDGELY_UNSUBSCRIBE_URL}}}">Unsubscribe</a>',
    'name' => 'October newsletter',
]);
$pidgely->broadcasts->send($broadcast->id, ['scheduled_at' => 'tomorrow at 10am']); // omit to send now
$pidgely->broadcasts->cancel($broadcast->id);
$pidgely->broadcasts->duplicate($broadcast->id);
$pidgely->broadcasts->update($broadcast->id, ['subject' => 'New subject']); // drafts only
$pidgely->broadcasts->get($broadcast->id); // ->stats per status
$pidgely->broadcasts->list();
$pidgely->broadcasts->remove($broadcast->id);
```

Pass `'send' => true` (with an optional `scheduled_at`) to `create` to send without a separate call. Every broadcast message includes RFC 8058 one-click unsubscribe. Personalisation supports `{{{FIRST_NAME|fallback}}}`, `{{{LAST_NAME}}}`, `{{{EMAIL}}}`, `{{{contact.<property>}}}` and `{{{RESEND_UNSUBSCRIBE_URL}}}` / `{{{PIDGELY_UNSUBSCRIBE_URL}}}`.

### Templates

```php
$template = $pidgely->templates->create([
    'name' => 'Welcome',
    'alias' => 'welcome',
    'from' => 'Acme <hello@acme.com>',
    'subject' => 'Welcome, {{{NAME}}}!',
    'html' => '<p>Hi {{{NAME}}}, your trial lasts {{{TRIAL_DAYS}}} days.</p>',
    'variables' => [
        ['key' => 'NAME', 'type' => 'string', 'fallback_value' => 'there'],
        ['key' => 'TRIAL_DAYS', 'type' => 'number', 'fallback_value' => 14],
    ],
]);
$pidgely->templates->publish($template->id); // the published version is what `template` sends

// Render the draft without sending; missingVariables lists variables with no value and no fallback
$preview = $pidgely->templates->preview('welcome', ['variables' => ['NAME' => 'Ana', 'TRIAL_DAYS' => 30]]);

$pidgely->templates->get('welcome'); // by id or alias (draft)
$pidgely->templates->update('welcome', ['subject' => 'Welcome aboard, {{{NAME}}}!']);
$pidgely->templates->duplicate('welcome');
$pidgely->templates->list();
$pidgely->templates->remove('welcome');
```

### Webhooks

```php
$webhook = $pidgely->webhooks->create([
    'endpoint' => 'https://acme.com/webhooks/pidgely',
    'events' => ['email.delivered', 'email.bounced', 'email.complained'],
]);
echo $webhook->signingSecret; // whsec_…, store it to verify deliveries

$pidgely->webhooks->list();
$pidgely->webhooks->get($webhook->id);
$pidgely->webhooks->update($webhook->id, ['status' => 'disabled']);
$pidgely->webhooks->rotateSigningSecret($webhook->id);
$pidgely->webhooks->remove($webhook->id);
```

Events: `email.sent`, `email.delivered`, `email.delivery_delayed`, `email.bounced`, `email.complained`, `email.failed`, `email.suppressed`, `domain.updated`, `contact.created`, `contact.updated`, `contact.deleted`.

### Suppressions

```php
$pidgely->suppressions->add(['email' => 'noreply@example.com']);
$pidgely->suppressions->list(['origin' => 'bounce']); // bounce | complaint | manual | unsubscribe
$pidgely->suppressions->get('noreply@example.com');   // by id or email
$pidgely->suppressions->remove('noreply@example.com');
$pidgely->suppressions->batch->add(['emails' => ['a@example.com', 'b@example.com']]); // up to 100
$pidgely->suppressions->batch->remove(['emails' => ['a@example.com']]);
```

## Verifying webhooks

Pidgely signs every delivery with the [Standard Webhooks](https://www.standardwebhooks.com) scheme. The same values are sent under both the `webhook-*` and `svix-*` header names. `Pidgely\Webhook::verify()` checks the HMAC-SHA256 signature in constant time, rejects timestamps more than 5 minutes from now, and returns the parsed event. Pass the **raw** request body. No API key is needed.

```php
use Pidgely\Exception\WebhookVerificationException;
use Pidgely\Webhook;

$payload = file_get_contents('php://input');
try {
    // Headers: a PSR-7 request, getallheaders(), or $_SERVER
    $event = Webhook::verify($payload, getallheaders(), getenv('PIDGELY_WEBHOOK_SECRET'));
} catch (WebhookVerificationException) {
    http_response_code(400);
    exit;
}
if ($event->type === 'email.bounced') {
    // $event->data['email_id'], $event->data['to'], ...
}
http_response_code(204);
```

`$pidgely->webhooks->verify(...)` is the same check. Set a different window with the `$tolerance` argument (seconds).

## Laravel

There is no separate Laravel package yet; two options work today.

**Mail transport via SMTP.** Keep using Laravel's `Mail` facade and mailables: point the `smtp` mailer at Pidgely's SMTP relay with an API key as the password (see the [SMTP relay guide](https://docs.pidgely.com/guides/smtp/); ports 587/2587 STARTTLS or 465/2465 TLS):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.pidgely.com
MAIL_PORT=587
MAIL_USERNAME=pidgely
MAIL_PASSWORD="${PIDGELY_API_KEY}"
MAIL_ENCRYPTION=tls
```

**Service binding.** For the full API (batch, broadcasts, contacts, idempotency...), bind the client in a service provider and inject it:

```php
// app/Providers/AppServiceProvider.php
use Pidgely\Client;

public function register(): void
{
    $this->app->singleton(Client::class, fn () => \Pidgely::client(config('services.pidgely.key')));
}

// config/services.php
'pidgely' => ['key' => env('PIDGELY_API_KEY')],

// anywhere
public function __invoke(Client $pidgely) { $pidgely->emails->send([...]); }
```

## Migrating from Resend

Pidgely serves the Resend REST API at the same paths, and this SDK mirrors `resend-php`:

```diff
- $resend = Resend::client(getenv('RESEND_API_KEY'));
+ $resend = Pidgely::client(getenv('PIDGELY_API_KEY'));

  $resend->emails->send(['from' => ..., 'to' => ..., 'subject' => ..., 'html' => ...]);
```

1. `composer remove resend/resend-php && composer require pidgely/pidgely-php guzzlehttp/guzzle`.
2. Replace `Resend::client` with `Pidgely::client` and swap `re_…` for your `pdg_…` key.
3. Verify your domain in Pidgely and publish the DNS records it returns.
4. If you verify webhooks, use `Pidgely\Webhook::verify()` with the `whsec_…` secret from your Pidgely webhook.

Parameter arrays use the same snake_case keys and the services keep their names (`emails`, `batch`, `domains`, `apiKeys`, `contacts`, `segments`/`audiences`, `broadcasts`, `templates`, `webhooks`). Responses are typed objects (`$email->id`); array access with the API's keys also works. Exceptions are `Pidgely\Exception\*`. Pidgely additions include `emails->attachments`, `apiKeys->update`, `segments->update/listContacts`, `contacts->batch`, `templates->preview`, `broadcasts->cancel/duplicate`, `suppressions`, the `stream` field on emails and built-in retries. Some Resend features are not supported yet: open/click tracking, topics, inbound email, automations, events and logs. See the migration guide at [docs.pidgely.com](https://docs.pidgely.com) for the full list.

## Development

```sh
composer update
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse   # level max
vendor/bin/phpunit           # mocked PSR-18 client, no network
```

### Publishing

Packagist reads releases straight from the Git tags of this repository; nothing is uploaded.

1. One-time setup: sign in at [packagist.org](https://packagist.org) with the GitHub account that administers `pidgely`, choose *Submit*, and enter `https://github.com/pidgely/pidgely-php`. Packagist then installs its GitHub hook (or use the Packagist GitHub app) so every push and tag updates the package automatically. Optionally store `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` (*Profile → API Token*) as repository secrets so [`release.yml`](.github/workflows/release.yml) also triggers an immediate update.
2. Update `CHANGELOG.md` and `Client::VERSION`, commit, wait for CI on `main`, then `git tag v0.2.0 && git push origin v0.2.0`. The release workflow checks the tag against `Client::VERSION`, runs the tests and creates the GitHub release.

## Links

- Documentation: https://docs.pidgely.com
- API reference (OpenAPI 3.1): [`openapi/pidgely.yaml`](openapi/pidgely.yaml)
- Other SDKs: [Node.js](https://github.com/pidgely/pidgely-node) · [Python](https://github.com/pidgely/pidgely-python) · [Go](https://github.com/pidgely/pidgely-go) · [Ruby](https://github.com/pidgely/pidgely-ruby)
- Security: see [SECURITY.md](https://github.com/pidgely/.github/blob/main/SECURITY.md)

## License

[MIT](LICENSE) © Caotec Serviços em Tecnologia Ltda
