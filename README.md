<p align="center">
  <img src="https://irnoti.com/favicon.svg" width="64" alt="irnoti">
</p>

<h1 align="center">Laravel SDK for the irnoti SMS Panel API</h1>

<p align="center">
  Official, typed, fluent Laravel client for <a href="https://irnoti.com">irnoti</a>'s public SMS panel API —
  send messages, run every service the panel offers, manage your phonebook and verify webhooks, without writing a single HTTP request by hand.
</p>

<p align="center">
  <a href="https://github.com/irnoti/laravel-sms-panel-api/actions/workflows/tests.yml"><img src="https://github.com/irnoti/laravel-sms-panel-api/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/irnoti/laravel-sms-panel-api"><img src="https://img.shields.io/packagist/v/irnoti/laravel-sms-panel-api.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/irnoti/laravel-sms-panel-api"><img src="https://img.shields.io/packagist/dt/irnoti/laravel-sms-panel-api.svg" alt="Total Downloads"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

---

## Why this package

irnoti gives every customer a REST API keyed by a per-account **access key** (`irn_live_…` / `irn_test_…`) — documented at [irnoti.com/docs](https://irnoti.com/docs) (interactive OpenAPI at `/api/docs` on the API host). This SDK is a thin, typed Laravel wrapper around it:

```php
Irnoti::sms()->send(lineId: 1, to: '09121234567', text: 'Your order has shipped!');
```

- **Full API coverage** — every endpoint reachable with an access key: SMS (send, quote, pattern, SOS, scheduled/gradual batches, message archive, lines), wallet, phonebook, provider services (regional, targeted, peyamap, smart, messenger, voice, international), link tracker, number banks, verify sessions, bot builder, VoIP — plus a webhook signature verifier.
- **Typed errors** — every failure is an `IrnotiApiException` carrying the HTTP status and the API's stable `code` (`insufficient_balance`, `invalid_recipients`, `no_price`, …) with convenience checks.
- **Laravel-native** — auto-discovered provider, facade, publishable config, requests through `Illuminate\Http\Client` so `Http::fake()` works in your tests.
- **Multi-tenant friendly** — bind a client to any key at runtime with `withApiKey()`.
- **Money-safe** — amounts are integer **Rial** values returned as **strings**; never cast them to float.
- **Sandbox** — an `irn_test_…` key with `lineId: 'sandbox'` sends nothing and costs nothing.

## Requirements

- PHP 8.2+
- Laravel 10, 11 or 12
- An irnoti access key from the **Web service (وب‌سرویس)** section of your panel. Each key has *scopes*, an optional IP whitelist and can be a sandbox key.

## Installation

```bash
composer require irnoti/laravel-sms-panel-api
php artisan vendor:publish --tag=sms-panel-api-config   # optional
```

```dotenv
IRNOTI_API_KEY=irn_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
IRNOTI_API_BASE_URL=https://api.irnoti.com
```

> The key is shown once at creation and only its hash is stored. Keep it server-side, never in a browser or app.

## Quick start

```php
use Irnoti\SmsPanelApi\Facades\Irnoti;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;

try {
    $line = Irnoti::lines()->list()[0]['id'];

    $sent = Irnoti::sms()->send(
        lineId: $line,
        to: '09121234567',
        text: 'Your verification code is 48213.',
        idempotencyKey: 'otp-9921',
    );
    // ['batchId' => '918', 'count' => 1, 'totalPrice' => '980', 'replayed' => false]
} catch (IrnotiApiException $e) {
    report($e);
}
```

Or inject `Irnoti\SmsPanelApi\IrnotiClient` (a container singleton). Every method returns the decoded JSON of the response (see PHPDoc for shapes) or throws `IrnotiApiException`.

## Quickstart

New here? Follow **[QUICKSTART.md](QUICKSTART.md)**: install, get a key, send your first (free, sandbox) SMS in 5 minutes.

## What can this SDK do? (function reference)

Everything hangs off one client. Each row below is a *resource*, and links to its section in the API docs at [irnoti.com/docs](https://irnoti.com/docs) (open the docs panel and look for the section named in the "Docs section" column).

| Resource | Docs section | What it is for | Functions |
|---|---|---|---|
| `Irnoti::sms()` | SMS | Send text messages | `send` (single / bulk / group / scheduled / gradual), `quote` (exact price, no send), `pattern` (approved OTP/transactional template), `sos` (guaranteed delivery with failover) |
| `Irnoti::messages()` | SMS → Messages | Look up sent messages | `list`, `get`, `check` |
| `Irnoti::batches()` | SMS → Batches | Control scheduled/gradual sends | `scheduled`, `cancel`, `pause`, `resume` |
| `Irnoti::lines()` | SMS → Lines | Your sender numbers (you need a line id to send) | `list` |
| `Irnoti::wallet()` | Wallet | Balance and history | `balance`, `transactions` |
| `Irnoti::phonebook()` | Phonebook | Contact groups and contacts | `groups`, `createGroup`, `deleteGroup`, `contacts`, `addContacts`, `importCsv`, `updateContact`, `deleteContact` |
| `Irnoti::services()` | Services | Provider products: `regional`, `targeted`, `peyamap`, `smart`, `messenger`, `voice`, `international` | `prices`, `internationalRates`, `list`, `quote`, `create`, `get`, `refresh`, `cancel`, `regional`, `targeted`, `peyamap`, `smart`, `messenger`, `voice`, `international` |
| `Irnoti::linkTracker()` | Link tracker | SMS with tracked links, A/B variants, holdout | `send`, `holdouts`, `sendNow`, `campaigns`, `campaign` |
| `Irnoti::numberBanks()` | Number banks | Send to purchased/filtered number banks | `list`, `facets`, `quote`, `send` |
| `Irnoti::verify()` | Verify | OTP verification sessions (SMS or bot) | `info`, `create`, `list`, `get`, `cancel`, `stats` |
| `Irnoti::bots()` | Bot builder | Read bots, send messages/OTP through them (creating bots is panel-only) | `info`, `list`, `get`, `stats`, `subscribers`, `messages`, `send`, `otp`, `shopOrders`, `shopStats` |
| `Irnoti::voip()` | VoIP | Click-to-call | `calls`, `price`, `call` |

Top-level helpers:

| Function | What it does |
|---|---|
| `Irnoti::` facade or inject `IrnotiClient` | Create the client |
| `Irnoti::withApiKey($key)` | New independent client for another customer key (multi-tenant) |
| `Webhook::verify($rawBody, $signature, $secret)` | `true`/`false`: is this webhook really from irnoti? |
| `Webhook::parse($rawBody, $signature, $secret)` | Verify and return the decoded event; fails if the signature is bad |
| `IrnotiApiException` | Raised/returned on any non-2xx answer; has `statusCode()`, `errorCode()`, `details()` and helpers like `isInsufficientBalance()` |

Every function is a thin wrapper over one HTTP endpoint and returns the API's JSON. Each method has a PHPDoc block with arguments and return shape; see `src/Resources/`.

> Typical flow: `Irnoti::lines()->list()` → `Irnoti::sms()->quote()` → `Irnoti::sms()->send()` (with `idempotencyKey`) → `Irnoti::messages()->get()` / webhook. Use an `irn_test_…` key and line id `sandbox` while experimenting: nothing is sent or charged.

## Scopes at a glance

| Scope | Allows |
| --- | --- |
| `sms.send` | send, pattern, SOS, batch cancel/pause/resume, link-tracker send, number-bank send, service orders, VoIP calls |
| `sms.read` | quote, messages, lines, scheduled, service prices/orders, number banks, VoIP history |
| `wallet.read` | balance and transactions |
| `phonebook.read` / `phonebook.write` | read / change groups and contacts |
| `bot.send` / `bot.read` | bot builder send+OTP / bots, subscribers, stats |
| `verify.send` / `verify.read` | create+cancel / read verify sessions |

A missing scope answers `403`. Sending also requires completed KYC (not for sandbox keys), and optional products (scheduled, gradual, SOS, pattern, link tracker, services, voip, …) must be enabled for your account.

## Usage

### Sending — `Irnoti::sms()`

```php
// One or many recipients and/or phonebook groups (max 10,000 numbers; Persian digits and +98 are fine).
Irnoti::sms()->send(lineId: 1, text: 'Flash sale!', to: ['09121234567', '09351234567'], groupIds: [12]);

// Cost without sending or charging.
Irnoti::sms()->quote(lineId: 1, text: 'Flash sale!', to: '09121234567');   // count, parts, totalPrice

// Later: 1 minute – 60 days ahead. The cost is reserved immediately.
Irnoti::sms()->send(lineId: 1, text: 'Reminder', to: '09121234567', scheduledAt: now()->addDay());

// Gradual: release 500 messages every 10 minutes (combinable with scheduledAt).
Irnoti::sms()->send(lineId: 1, text: 'Hi', groupIds: [12], gradual: ['chunkSize' => 500, 'intervalMinutes' => 10]);

// Approved template (pattern) — OTP / transactional.
Irnoti::sms()->pattern(code: 'k3f9a2', to: '09121234567', params: ['code' => '4821']);

// Critical alerts over the platform's own routes with automatic provider failover (≤ 200 recipients).
Irnoti::sms()->sos(to: ['09121234567'], text: 'Service down');
```

Use `idempotencyKey:` on every send you might retry: the same key returns the original result with `replayed: true` instead of sending twice.

### Messages, batches, lines

```php
Irnoti::messages()->list(take: 50);              // newest first; page with before: <last id>
Irnoti::messages()->get(5521);                   // status: queued | sent | delivered | failed
Irnoti::messages()->check(5521);                 // ask the operator now (30/min)

Irnoti::batches()->scheduled();
Irnoti::batches()->cancel(918);                  // refunds what has not been released
Irnoti::batches()->pause(918);
Irnoti::batches()->resume(918);

Irnoti::lines()->list();                         // ids to use as lineId
```

### Wallet

```php
Irnoti::wallet()->balance();        // ['balance' => '1840000', 'currency' => 'IRR']
Irnoti::wallet()->transactions();
```

### Phonebook

```php
$group = Irnoti::phonebook()->createGroup('VIP');                     // 409 if the name exists
Irnoti::phonebook()->addContacts($group['id'], [['mobile' => '09121234567', 'name' => 'Ali']]);
Irnoti::phonebook()->importCsv($group['id'], file_get_contents('contacts.csv'));
Irnoti::phonebook()->contacts($group['id'], take: 100, q: 'Ali');
Irnoti::phonebook()->updateContact(7, ['company' => 'Acme', 'optedOut' => false]);
Irnoti::phonebook()->deleteContact(7);
Irnoti::phonebook()->groups();
Irnoti::phonebook()->deleteGroup($group['id']);
```

### Provider services — `Irnoti::services()`

Regional, targeted, peyamap, smart, messenger, voice and international orders share one flow: **quote → create → refresh → cancel**.

```php
Irnoti::services()->prices();

$body = ['province' => 'tehran', 'count' => 5000, 'text' => 'Sale today'];
Irnoti::services()->quote('regional', $body);
$order = Irnoti::services()->regional($body);

Irnoti::services()->targeted(['gender' => 'female', 'ageFrom' => 20, 'ageTo' => 35, 'count' => 1000, 'text' => '…']);
Irnoti::services()->peyamap(['operator' => 'mci', 'areas' => [['name' => 'Valiasr', 'count' => 500]], 'text' => '…']);
Irnoti::services()->smart(['09121234567'], 'Hello');
Irnoti::services()->messenger('bale', ['09121234567'], 'Hello');   // bale | eitaa | whatsapp
Irnoti::services()->voice(['09121234567'], 'Your order is ready');
Irnoti::services()->international(['+4915112345678'], 'Hello');

Irnoti::services()->get('regional', $order['id']);
Irnoti::services()->refresh('regional', $order['id']);
Irnoti::services()->cancel('regional', $order['id']);              // only before the provider starts
```

### Link tracker

```php
Irnoti::linkTracker()->send(
    lineId: 1, text: 'Sale: {link}', url: 'https://shop.example/sale',
    to: ['09121234567'], variants: ['Big sale: {link}'], holdoutPercent: 20,
);
Irnoti::linkTracker()->campaigns();
Irnoti::linkTracker()->campaign($batchId);   // clicks per link
Irnoti::linkTracker()->holdouts();
Irnoti::linkTracker()->sendNow($holdoutId);
```

### Number banks

```php
Irnoti::numberBanks()->list();
Irnoti::numberBanks()->facets(2);
Irnoti::numberBanks()->quote(bankId: 2, count: 1000, lineId: 1, text: 'Promo', filters: ['operator' => 'mci']);
Irnoti::numberBanks()->send(bankId: 2, count: 1000, lineId: 1, text: 'Promo', filters: ['operator' => 'mci'], idempotencyKey: 'promo-1');
```

### Verify sessions

```php
$s = Irnoti::verify()->create(to: '09121234567', options: ['length' => 6, 'ttlSec' => 300, 'callbackUrl' => 'https://app.example/verified']);
Irnoti::verify()->get($s['id']);
Irnoti::verify()->cancel($s['id']);
Irnoti::verify()->list(); Irnoti::verify()->stats(); Irnoti::verify()->info();
```

### Bot builder & VoIP

```php
Irnoti::bots()->list();
Irnoti::bots()->send($botId, to: [...], message: [...], idempotencyKey: 'msg-1');   // 202, queued
Irnoti::bots()->otp($botId, to: [...], length: 6);
Irnoti::bots()->subscribers($botId); Irnoti::bots()->messages($botId); Irnoti::bots()->stats($botId);
Irnoti::bots()->shopOrders($botId, status: 'new');

Irnoti::voip()->price();
Irnoti::voip()->call(from: '09121234567', to: '09351234567');
Irnoti::voip()->calls();
```

Bot creation/configuration is panel-only; the `to`/`message` shapes follow the panel's bot sender.

### Delivery webhooks

Register the URL and copy the `whsec_…` signing key in the panel, then verify every call against the **raw** body:

```php
use Irnoti\SmsPanelApi\Support\Webhook;

$event = Webhook::parse($request->getContent(), $request->header('X-Irnoti-Signature'), config('services.irnoti.webhook_secret'));
// ['id' => '4821', 'event' => 'message.delivered', 'createdAt' => '…', 'data' => ['messageId' => '5521', 'status' => 'delivered', …]]
```

`Webhook::verify()` returns a bool instead of throwing. Events (`message.sent|delivered|failed`) may repeat or arrive out of order — de-duplicate on `X-Irnoti-Delivery`. Signatures older than 5 minutes are rejected. See `examples/webhook-controller.php`.

## Error handling

```php
try {
    Irnoti::sms()->send(lineId: 1, to: '0912', text: 'hi');
} catch (IrnotiApiException $e) {
    $e->statusCode();   // 400
    $e->errorCode();    // "invalid_recipients" ('' for plain 401/403/404)
    $e->details();      // ['0912']  — invalid numbers / missing template params / field errors
    $e->getMessage();
}
```

| Status | Meaning | Helper |
| --- | --- | --- |
| 400 | invalid request (`validation_error`, `invalid_recipients`, `missing_params`, `invalid_schedule`) | `isValidationError()`, `isInvalidRecipients()` |
| 401 | bad/revoked key or IP not whitelisted | `isUnauthenticated()` |
| 402 | `insufficient_balance` | `isInsufficientBalance()` |
| 403 | missing scope, KYC incomplete, feature disabled, template not approved | `isForbidden()` |
| 404 | not found | `isNotFound()` |
| 409 | conflict (duplicate group, no SOS route) | `isConflict()` |
| 422 | `no_price` — no tariff for the service | `isNoPrice()` |
| 429 | rate limited (send 600/min, pattern 1200/min per IP) | `isRateLimited()` |
| 5xx | server error — retry with the same idempotency key | `isServerError()` |

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
| `base_url`    | `IRNOTI_API_BASE_URL`    | `https://api.irnoti.com` | Root of the API (the client appends `/v1`).                      |
| `key`         | `IRNOTI_API_KEY`         | —                        | Your access key from `the API keys page`.                      |
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

<p align="center">Built and maintained by <a href="https://irnoti.com">irnoti</a> — full API reference at <a href="https://irnoti.com/docs">irnoti.com/docs</a>.</p>
