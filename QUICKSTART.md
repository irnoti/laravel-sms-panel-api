# Quickstart (Laravel)

Goal: send your first SMS with the irnoti Laravel SDK in about 5 minutes, without spending money.

Full function list: [README → function reference](README.md#what-can-this-sdk-do-function-reference). Full API reference (the docs panel): [irnoti.com/docs](https://irnoti.com/docs).

## 1. Install

```bash
composer require irnoti/laravel-sms-panel-api
```

Requires PHP 8.2+ and Laravel 10, 11 or 12. The service provider and the `Irnoti` facade are auto-discovered.

## 2. Get an access key

In the irnoti panel create an **access key** (docs panel → *Authentication / Access keys*).

- `irn_test_…` — sandbox key: nothing is sent, nothing is charged. **Start with this.**
- `irn_live_…` — real key: sends real SMS and charges your wallet.

Keep it in an environment variable, never in source control or client-side code:

```bash
export IRNOTI_API_KEY=irn_test_xxxxxxxx
```

Put the key in `.env` (publish the config with `php artisan vendor:publish --tag=sms-panel-api-config` if you want to change defaults):

```dotenv
IRNOTI_API_KEY=irn_test_xxxxxxxx
```

## 3. Send a test SMS (sandbox, free)

```php
use Irnoti\SmsPanelApi\Facades\Irnoti;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;

try {
    // How much would it cost? (no send, no charge)
    $quote = Irnoti::sms()->quote(lineId: 'sandbox', text: 'Hello', to: '09121234567');

    // Send. idempotencyKey makes retries safe: the same key never sends twice.
    $result = Irnoti::sms()->send(lineId: 'sandbox', to: '09121234567', text: 'Hello', idempotencyKey: 'my-first-send');
    // ['batchId' => …, 'count' => 1, 'totalPrice' => '…', 'replayed' => false]
} catch (IrnotiApiException $e) {
    report($e);
}
```

Try it in `php artisan tinker`, or inject `Irnoti\SmsPanelApi\IrnotiClient` into your classes instead of using the facade.

`totalPrice` is an integer amount in **Rial, returned as a string** — don't convert it to a float.

## 4. Go live

1. Switch to an `irn_live_…` key.
2. Find your sender number: list your lines and use its `id` instead of `"sandbox"`.
3. Check money: the wallet `balance` function.
4. Always `quote` first for big sends, and always pass an idempotency key to `send`.

```php
$line = Irnoti::lines()->list()[0]['id'];
Irnoti::sms()->send(lineId: $line, to: ['09121234567'], text: 'Hello', idempotencyKey: 'order-1001');
```

## 5. Common tasks — which function, which docs section

| I want to… | Call | Docs section |
|---|---|---|
| Send one/many SMS | `Irnoti::sms()->send(lineId: $line, text: '…', to: [...])` | SMS |
| Send to a contact group | `Irnoti::sms()->send(lineId: $line, text: '…', groupIds: [12])` | SMS + Phonebook |
| Schedule / drip-send | `Irnoti::sms()->send(..., scheduledAt: now()->addDay(), gradual: ['chunkSize' => 500, 'intervalMinutes' => 10])` | SMS |
| Send an OTP with an approved template | `Irnoti::sms()->pattern(code: 'template_code', to: '0912…', params: ['code' => '4821'])` | SMS → Pattern |
| Check delivery | `Irnoti::messages()->get($id)` or a webhook | SMS → Messages / Webhooks |
| Manage contacts | `Irnoti::phonebook()->createGroup(...)`, `addContacts(...)`, `importCsv(...)` | Phonebook |
| Check balance | `Irnoti::wallet()->balance()` | Wallet |
| Phone verification flow | `Irnoti::verify()->create(to: '0912…', options: ['ttlSec' => 300])` | Verify |

(Every function: [README reference table](README.md#what-can-this-sdk-do-function-reference).)

## 6. Handle errors

```php
try {
    Irnoti::sms()->send(lineId: $line, to: '0912…', text: 'Hi');
} catch (IrnotiApiException $e) {
    if ($e->isInsufficientBalance()) { /* top up the wallet */ }
    elseif ($e->isRateLimited()) { /* slow down */ }
    elseif ($e->isInvalidRecipients()) { logger()->warning('bad numbers', $e->details()); }
    else { throw $e; }
}
```

Status → helper: `401` unauthenticated, `402` insufficient balance, `403` forbidden (missing scope / KYC), `404` not found, `422` `no_price`, `429` rate limited (send limit 600 req/min).

## 7. Receive delivery webhooks

Verify the **raw** request body (not re-serialised JSON) against the `X-Irnoti-Signature` header with your `whsec_…` secret. Signatures older than 5 minutes are rejected; events can repeat, so de-duplicate on `X-Irnoti-Delivery`.

```php
// routes/api.php: Route::post('/irnoti/webhook', IrnotiWebhookController::class);  (exclude from CSRF)
use Illuminate\Http\Request;
use Irnoti\SmsPanelApi\Support\Webhook;

class IrnotiWebhookController
{
    public function __invoke(Request $request)
    {
        try {
            $event = Webhook::parse($request->getContent(), (string) $request->header('X-Irnoti-Signature'), config('services.irnoti.webhook_secret'));
        } catch (\InvalidArgumentException) {
            abort(400);
        }
        logger()->info($event['event'], $event['data']);
        return response()->noContent();
    }
}
```

A ready-made version is in [`examples/webhook-controller.php`](examples/webhook-controller.php).

## Next

- Browse everything in the [function reference](README.md#what-can-this-sdk-do-function-reference).
- Full usage guide with every call: the [Usage section of the README](README.md#usage).
- Multi-tenant app? `Irnoti::withApiKey($customerKey)` (see `examples/multi-tenant-notification.php`).
- Tests: `Http::fake()` works because requests go through `Illuminate\Http\Client`.
