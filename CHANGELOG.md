# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `templates->preview($idOrAlias, ['variables' => [...]])` renders a template draft with variables (`POST /templates/{id_or_alias}/preview`) and returns a `TemplatePreview`.
- `contacts->batch(['contacts' => [...], 'segment_id' => ...])` creates or updates up to 1,000 contacts in one request (`POST /contacts/batch`) and returns a `ContactBatchResult` (`data` as `ContactBatchItem`s); invalid rows are reported in `errors` with their index.

## [0.1.0] - 2026-10-12

### Added

- First release of the official Pidgely SDK for PHP (8.1+).
- `Pidgely::client($apiKey, $options)` and `Pidgely\Client`, reading `PIDGELY_API_KEY` / `PIDGELY_BASE_URL`. PSR-18 / PSR-17 HTTP via `php-http/discovery`, with Guzzle used when installed.
- Services: `emails` (with `attachments`), `batch`, `domains`, `apiKeys`, `contacts` (with `segments`), `segments` (with the `audiences` alias), `broadcasts`, `templates`, `webhooks` and `suppressions` (with `batch`).
- Typed, read-only response DTOs (`Pidgely\Model\*`) with array access to the raw response, and an iterable `Collection` for lists.
- Exceptions for every failure: `PidgelyException` and `ValidationException`, `AuthenticationException`, `PermissionDeniedException`, `NotFoundException`, `ConflictException`, `RateLimitException`, `InternalServerException`, `ApiConnectionException`, `ApiTimeoutException`, `MissingApiKeyException`.
- `idempotency_key` request option (`Idempotency-Key` header), plus per-request `headers`, `timeout` and `max_retries`.
- Automatic retries with exponential backoff and jitter on 408/429/5xx/network errors, honouring `Retry-After`. A POST is only retried when it carries an idempotency key, so an email is never sent twice.
- The `pidgely-php/<version>` user agent. The API key never appears in dumps, exceptions or (PHP 8.2+) stack traces.
- `Pidgely\Webhook::verify()` for Standard Webhooks (Svix) signatures, with constant-time comparison and a 5-minute timestamp tolerance; `Pidgely\Attachment` helpers.

[Unreleased]: https://github.com/pidgely/pidgely-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/pidgely/pidgely-php/releases/tag/v0.1.0
