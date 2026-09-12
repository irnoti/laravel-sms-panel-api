# Contributing

Thanks for considering a contribution to the irnoti Laravel SDK.

## Getting started

```bash
git clone https://github.com/irnoti/laravel-sms-panel-api.git
cd laravel-sms-panel-api
composer install
```

## Running the test suite

```bash
composer test
```

Tests run against [Orchestra Testbench](https://packages.tool/orchestra/testbench) with the real `Illuminate\Http\Client\Factory` faked via `Http::fake()` — no live HTTP calls, no real access key needed.

## Pull requests

- Keep one logical change per pull request.
- Add or update tests for any behavior change.
- Match the existing code style (PSR-12; run `vendor/bin/pint` if you have [Laravel Pint](https://github.com/laravel/pint) available).
- Update `CHANGELOG.md` under an `[Unreleased]` heading.

## Reporting issues

Please include:
- The SDK version and Laravel version.
- A minimal reproduction (the resource method call + a redacted response body, if relevant).
- The `error.code` from the API response, if the issue involves an unexpected `IrnotiApiException`.

Never include a real access key in an issue or pull request — revoke it from `/dashboard/api-keys` first if one was ever exposed.
