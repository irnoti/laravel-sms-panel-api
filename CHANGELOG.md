# Changelog

All notable changes to this package are documented here. This project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-09-12

### Added
- Initial release of the official irnoti SMS Panel API SDK for Laravel.
- `Irnoti` facade and `IrnotiClient` covering every endpoint of the [irnoti public panel API](https://irnoti.com/developers):
  account credit/numbers, message archive, single/bulk send, scheduled, gradual, regional, targeted, peyamap, smart, and link-tracker sends, and phonebook contacts/groups.
- Typed `IrnotiApiException` mapping the API's stable `error.code` values to convenience checks (`isInsufficientBalance()`, `isRateLimited()`, `isUnauthenticated()`, `isValidationError()`).
- Auto-discovered Laravel service provider, publishable config, and multi-key support via `withApiKey()`.
- Test suite built on Orchestra Testbench + `Http::fake()`, and a GitHub Actions matrix across PHP 8.2/8.3 and Laravel 10/11/12.
