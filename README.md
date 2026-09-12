<p align="center">
  <img src="https://irnoti.com/favicon.svg" width="64" alt="irnoti">
</p>

<h1 align="center">Laravel SDK for the irnoti SMS Panel API</h1>

<p align="center">
  Official, typed, fluent Laravel client for <a href="https://irnoti.com">irnoti</a>'s public SMS panel API —
  send messages, run every campaign mode the dashboard offers, and sync your phonebook, without writing a single HTTP request by hand.
</p>

<p align="center">
  <a href="https://github.com/irnoti/laravel-sms-panel-api/actions/workflows/tests.yml"><img src="https://github.com/irnoti/laravel-sms-panel-api/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/irnoti/laravel-sms-panel-api"><img src="https://img.shields.io/packagist/v/irnoti/laravel-sms-panel-api.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/irnoti/laravel-sms-panel-api"><img src="https://img.shields.io/packagist/dt/irnoti/laravel-sms-panel-api.svg" alt="Total Downloads"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

---

## Why this package

irnoti gives every customer a REST API keyed by a per-account **access key** instead of a username/password — the same API documented at [irnoti.com/developers](https://irnoti.com/developers). This SDK is a thin, typed Laravel wrapper around that API, so your application talks to irnoti through readable PHP method calls instead of hand-built HTTP requests and ad-hoc JSON parsing:

```php
Irnoti::sms()->send(to: '09121234567', message: 'Your order has shipped!');
```

- **Full API coverage** — every endpoint irnoti exposes today: account, message archive, single/bulk send, and all seven campaign modes (scheduled, gradual, regional, targeted, peyamap, smart, link-tracker), plus phonebook contacts/groups.
- **One exception type, stable error codes** — every API error becomes an `IrnotiApiException` carrying the same namespaced `error.code` the raw API returns (`sms.insufficient_balance`, `auth.invalid_key`, …), with convenience checks so you rarely need to compare strings.
- **Laravel-native** — auto-discovered service provider, a facade, a publishable config file, and requests made through Laravel's own HTTP client (`Illuminate\Http\Client`), so `Http::fake()` works out of the box in your tests.
- **Multi-tenant friendly** — bind a client to any access key at runtime with `withApiKey()`, for apps that hold one irnoti key per customer rather than one global key.
- **Zero surprises** — the SDK does not guess at business rules (pricing, ability scopes, recipient caps); it only shapes and sends the request the same way the [API reference](https://irnoti.com/developers) describes, and surfaces exactly what irnoti answers.

## Requirements

- PHP 8.2+
- Laravel 10, 11 or 12 (`illuminate/support` + `illuminate/http`)
- An irnoti account with an access key, minted from **`/dashboard/api-keys`** on your panel

## Installation

```bash
composer require irnoti/laravel-sms-panel-api
```

The service provider and `Irnoti` facade are auto-discovered — no manual registration needed.

Publish the config file (optional; sensible defaults come from environment variables):

```bash
php artisan vendor:publish --tag=sms-panel-api-config
```

Add your access key to `.env`:

```dotenv
IRNOTI_API_KEY=irnt_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
IRNOTI_API_BASE_URL=https://api.irnoti.com
```

> Treat the access key like a password. It is only ever shown once, at creation time, in `/dashboard/api-keys` — irnoti stores nothing but its hash. Revoke and reissue it from that same page if it's ever exposed.

## Quick start

```php
use Irnoti\SmsPanelApi\Facades\Irnoti;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;

try {
    $credit = Irnoti::account()->credit();
    // ['wallet_balance_toman' => 128000, 'panel_credit_sms' => 940, ...]

    $sent = Irnoti::sms()->send(
        to: '09121234567',
        message: 'Your verification code is 48213.',
    );
    // ['id' => 5821, 'status' => 'queued', 'cost_toman' => 150]
} catch (IrnotiApiException $e) {
    report($e);
}
```

Or inject `Irnoti\SmsPanelApi\IrnotiClient` wherever you'd rather avoid the facade — it's bound as a singleton in the container.

## Usage

Every resource method returns the plain PHP array decoded from the API's `data` field (see each method's PHPDoc for its exact shape) or throws `IrnotiApiException` on failure. List endpoints always come back as `['items' => [...], 'meta'? => [...]]`.

### Account

```php
Irnoti::account()->credit();   // wallet balance + upstream panel credit
Irnoti::account()->numbers();  // sender numbers available to this account
```

### Message archive

```php
Irnoti::messages()->list(perPage: 50);
Irnoti::messages()->get(id: 5821);
```

### Single & bulk send

```php
// One recipient, billed immediately. Requires the `sms.send` ability.
Irnoti::sms()->send(to: '09121234567', message: 'Hello!', from: '3000123456');

// Up to 200 recipients — mix raw numbers with phonebook group ids.
// Requires the `sms.bulk` ability.
Irnoti::sms()->bulk(
    numbers: ['09121234567', '09129876543'],
    message: 'Flash sale, today only!',
    groupIds: [12],
);
```

### Scheduled send — `scheduled.sms`

Registered with the SMS provider itself, so the send fires even if your app is offline; cancel only works before it fires.

```php
Irnoti::scheduled()->list();

Irnoti::scheduled()->create(
    scheduledAt: '2026-09-20 09:00:00',
    message: 'Reminder: your subscription renews tomorrow.',
    numbers: ['09121234567'],
);

Irnoti::scheduled()->cancel(id: 91); // full refund if it hasn't fired yet
```

### Gradual send — `sms.gradual`

Drains up to 20,000 recipients in chunks over time, run on irnoti's own side — pause and resume at will.

```php
Irnoti::gradual()->create(
    chunkSize: 200,
    intervalMinutes: 5,
    message: 'New collection just dropped.',
    groupIds: [3],
);

Irnoti::gradual()->pause(id: 44);
Irnoti::gradual()->resume(id: 44);
Irnoti::gradual()->cancel(id: 44); // refunds the undrained portion
```

### Regional send — `sms.regional`

No recipient list — target a province (+ optional city) and a message count; irnoti's provider picks the numbers.

```php
Irnoti::regional()->create(
    province: 'تهران',
    count: 500,
    message: "Today's special offer, just for Tehran.",
);
```

### Targeted send — `sms.targeted`

The demographic sibling of regional send — filter by gender, age range, province/city, operator, or job (at least one filter is required).

```php
Irnoti::targeted()->create(
    count: 1000,
    message: 'A deal built for you.',
    gender: 'male',
    ageMin: 20,
    ageMax: 35,
    province: 'اصفهان',
);
```

### Peyamap send — `sms.peyamap`

Target one mobile operator's postal-code bank across one or more map areas, each with its own count.

```php
Irnoti::peyamap()->create(
    operator: 'mci',
    areas: [
        ['name' => 'District 3', 'count' => 300],
        ['name' => 'District 6', 'count' => 200],
    ],
    message: 'Grand opening near you!',
);
```

### Smart send — `sms.smart`

Your own recipient list, delivered "smart": a push notification first, a billed SMS fallback only for whoever didn't see it. Immediate-only; pull the delivery split on demand.

```php
$send = Irnoti::smart()->create(message: 'Your appointment is tomorrow at 5 PM.', numbers: ['09121234567']);

$status = Irnoti::smart()->refresh(id: $send['id']);
// ['smart_count' => 1, 'sms_count' => 0, 'failed_count' => 0, 'pending_count' => 0, ...]
```

### Link tracker — `sms.link_tracker`

Your own recipient list + a destination URL; irnoti mints a unique short link per recipient and reports clicks back.

```php
$campaign = Irnoti::linkTracker()->create(
    targetUrl: 'https://yourshop.example/sale',
    message: 'Check out our sale: {link}',
    numbers: ['09121234567', '09129876543'],
);

$analytics = Irnoti::linkTracker()->get(id: $campaign['id']);
// ['total_clicks' => 2, 'ctr_percent' => 100.0, 'links' => [...], ...]
```

### Phonebook

```php
Irnoti::phonebook()->contacts()->list(keyword: 'Ali');
Irnoti::phonebook()->contacts()->create(mobile: '09121234567', attributes: ['first_name' => 'Ali'], groupIds: [1]);
Irnoti::phonebook()->contacts()->update(id: 7, attributes: ['company' => 'Acme']);
Irnoti::phonebook()->contacts()->delete(id: 7);

Irnoti::phonebook()->groups()->list();
Irnoti::phonebook()->groups()->create(name: 'VIP customers');
```

## Error handling

Every failure — validation, an unauthenticated key, an insufficient wallet, a rate limit — is raised as a single `Irnoti\SmsPanelApi\Exceptions\IrnotiApiException`, carrying the exact fields the API answered with:

```php
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;

try {
    Irnoti::sms()->send(to: '09121234567', message: str_repeat('x', 1000));
} catch (IrnotiApiException $e) {
    $e->errorCode();      // e.g. "validation.failed"
    $e->statusCode();     // e.g. 422
    $e->details();        // e.g. ['message' => ['The message field must not be greater than 600 characters.']]
    $e->getMessage();     // English, human-readable

    // Convenience checks for the most common cases:
    $e->isInsufficientBalance();
    $e->isRateLimited();
    $e->isUnauthenticated();
    $e->isValidationError();
}
```

See the full `error.code` reference on [irnoti.com/developers](https://irnoti.com/developers).

## Multi-tenant usage

If your application holds one irnoti access key per customer rather than a single global key, don't rely on the config-bound singleton — bind a client to the key you need at request time:

```php
use Irnoti\SmsPanelApi\Facades\Irnoti;

$client = Irnoti::withApiKey($customer->irnoti_api_key);

$client->sms()->send(to: $customer->mobile, message: 'Welcome aboard!');
```

`withApiKey()` returns a new, independent client — it never mutates the shared singleton.

## Configuration reference

`config/sms-panel-api.php`:

| Key           | Environment variable     | Default                  | Description                                                      |
| ------------- | ------------------------ | ------------------------ | ---------------------------------------------------------------- |
| `base_url`    | `IRNOTI_API_BASE_URL`    | `https://api.irnoti.com` | Root of the panel API.                                           |
| `key`         | `IRNOTI_API_KEY`         | —                        | Your access key from `/dashboard/api-keys`.                      |
| `timeout`     | `IRNOTI_API_TIMEOUT`     | `15`                     | Per-request timeout, in seconds.                                 |
| `retry.times` | `IRNOTI_API_RETRY_TIMES` | `1`                      | Attempts for transient network failures (`1` disables retrying). |
| `retry.sleep` | `IRNOTI_API_RETRY_SLEEP` | `200`                    | Delay between retries, in milliseconds.                          |

## Testing

```bash
composer test
```

The suite is built on [Orchestra Testbench](https://github.com/orchestral/testbench) and Laravel's `Http::fake()` — no real access key or network access required. See `tests/` for examples of faking success and error responses per resource.

## Security

If you discover a security vulnerability in this SDK, please email **security@irnoti.com** rather than opening a public issue.

## Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License

Released under the [MIT License](LICENSE.md).

---

<p align="center">Built and maintained by <a href="https://irnoti.com">irnoti</a> — full API reference at <a href="https://irnoti.com/developers">irnoti.com/developers</a>.</p>
