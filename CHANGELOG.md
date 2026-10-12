# Changelog

All notable changes to this package are documented here. This project follows [Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-10-10

Rewritten for the new irnoti platform API (`https://api.irnoti.com/v1`). **Breaking**: the legacy `/v1/panel` service is no longer targeted.

### Changed
- Base path is now `/v1`; keys are `irn_live_…` / `irn_test_…` (sandbox keys send nothing, cost nothing).
- Responses are the bare JSON bodies (no `{success,data}` envelope); money is integer Rial as strings (was Toman integers).
- Request bodies use the API's camelCase fields (`lineId`, `groupIds`, `text`, `scheduledAt`, …); the `from`/`message` names are gone.
- `IrnotiApiException` now maps the HTTP status and the API's `code`/`details`; the old namespaced codes (`sms.insufficient_balance`, `auth.invalid_key`, …) no longer exist.
- `Idempotency-Key` supported on every send.

### Added
- Resources: `sms()` (send, quote, pattern, SOS), `messages()`, `batches()` (scheduled, cancel, pause, resume), `lines()`, `wallet()`, `phonebook()` (groups, contacts, CSV import), `services()` (regional, targeted, peyamap, smart, messenger, voice, international), `linkTracker()`, `numberBanks()`, `verify()`, `bots()`, `voip()`.
- `Support\Webhook` to verify and parse signed delivery webhooks.
- New exception helpers: `isForbidden()`, `isNotFound()`, `isConflict()`, `isNoPrice()`, `isInvalidRecipients()`, `isServerError()`.

### Removed
- `account()`, `scheduled()`, `gradual()`, `regional()`, `targeted()`, `peyamap()`, `smart()` top-level resources and the contacts/groups sub-resources — replaced as listed above (see the README for the mapping).

## [1.0.0] - 2026-09-12

### Added
- Initial release of the official irnoti SMS Panel API SDK for Laravel.
- `Irnoti` facade and `IrnotiClient` covering every endpoint of the [irnoti public panel API](https://irnoti.com/developers):
  account credit/numbers, message archive, single/bulk send, scheduled, gradual, regional, targeted, peyamap, smart, and link-tracker sends, and phonebook contacts/groups.
- Typed `IrnotiApiException` mapping the API's stable `error.code` values to convenience checks (`isInsufficientBalance()`, `isRateLimited()`, `isUnauthenticated()`, `isValidationError()`).
- Auto-discovered Laravel service provider, publishable config, and multi-key support via `withApiKey()`.
- Test suite built on Orchestra Testbench + `Http::fake()`, and a GitHub Actions matrix across PHP 8.2/8.3 and Laravel 10/11/12.
