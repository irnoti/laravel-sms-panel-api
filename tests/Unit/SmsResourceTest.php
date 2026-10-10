<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class SmsResourceTest extends TestCase
{
    public function test_send_posts_camel_case_body_with_idempotency_header(): void
    {
        Http::fake([
            'irnoti.test/api/v1/sms/send' => Http::response(['batchId' => '918', 'count' => 2, 'totalPrice' => '1960', 'replayed' => false], 201),
        ]);

        $result = app(IrnotiClient::class)->sms()->send(
            lineId: 1,
            text: 'hello',
            to: ['09121234567', '09351234567'],
            groupIds: [12],
            idempotencyKey: 'order-1042',
        );

        $this->assertSame('918', $result['batchId']);
        $this->assertSame('1960', $result['totalPrice']);

        Http::assertSent(fn ($r) => $r->url() === 'https://irnoti.test/api/v1/sms/send'
            && $r->method() === 'POST'
            && $r['lineId'] === '1'
            && $r['to'] === ['09121234567', '09351234567']
            && $r['groupIds'] === ['12']
            && $r['text'] === 'hello'
            && ! array_key_exists('scheduledAt', $r->data())
            && $r->hasHeader('Idempotency-Key', 'order-1042')
            && $r->hasHeader('Authorization', 'Bearer irn_test_key'));
    }

    public function test_send_formats_scheduled_date_and_gradual(): void
    {
        Http::fake(['*' => Http::response(['batchId' => '1', 'count' => 1, 'totalPrice' => '980', 'replayed' => false])]);

        app(IrnotiClient::class)->sms()->send(
            lineId: 'sandbox',
            text: 'later',
            to: '09121234567',
            scheduledAt: new \DateTimeImmutable('2026-10-20T09:00:00+00:00'),
            gradual: ['chunkSize' => 500, 'intervalMinutes' => 10],
        );

        Http::assertSent(fn ($r) => $r['lineId'] === 'sandbox'
            && $r['to'] === '09121234567'
            && $r['scheduledAt'] === '2026-10-20T09:00:00+00:00'
            && $r['gradual'] === ['chunkSize' => 500, 'intervalMinutes' => 10]);
    }

    public function test_pattern_sends_params_object_even_when_empty(): void
    {
        Http::fake(['*' => Http::response(['batchId' => '919', 'count' => 1, 'totalPrice' => '980', 'replayed' => false])]);

        app(IrnotiClient::class)->sms()->pattern('k3f9a2', '09121234567', ['code' => '4821']);
        app(IrnotiClient::class)->sms()->pattern('k3f9a2', '09121234567');

        Http::assertSent(fn ($r) => str_contains($r->body(), '"params":{"code":"4821"}'));
        Http::assertSent(fn ($r) => str_contains($r->body(), '"params":{}'));
    }

    public function test_quote_and_sos(): void
    {
        Http::fake([
            '*/sms/quote' => Http::response(['count' => 1, 'parts' => 1, 'totalPrice' => '980']),
            '*/sms/sos' => Http::response(['batchId' => '920', 'count' => 1, 'totalPrice' => '5000', 'replayed' => false]),
        ]);

        $client = app(IrnotiClient::class);
        $this->assertSame('980', $client->sms()->quote(1, 'hi', '09121234567')['totalPrice']);
        $this->assertSame('5000', $client->sms()->sos(['09121234567'], 'down!', 'alert-77')['totalPrice']);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/sms/sos') && $r->hasHeader('Idempotency-Key', 'alert-77'));
    }

    public function test_insufficient_balance_maps_to_typed_exception(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 402, 'code' => 'insufficient_balance', 'message' => 'Insufficient balance'], 402)]);

        try {
            app(IrnotiClient::class)->sms()->send(1, 'hi', '09121234567');
            $this->fail('Expected IrnotiApiException.');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isInsufficientBalance());
            $this->assertSame(402, $e->statusCode());
            $this->assertSame('insufficient_balance', $e->errorCode());
            $this->assertSame('Insufficient balance', $e->getMessage());
        }
    }

    public function test_invalid_recipients_exposes_details(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 400, 'code' => 'invalid_recipients', 'details' => ['0912']], 400)]);

        try {
            app(IrnotiClient::class)->sms()->send(1, 'hi', '0912');
            $this->fail('Expected IrnotiApiException.');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isInvalidRecipients());
            $this->assertTrue($e->isValidationError());
            $this->assertSame(['0912'], $e->details());
        }
    }

    public function test_plain_nest_errors_have_no_code(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 401, 'message' => 'Invalid API key', 'error' => 'Unauthorized'], 401)]);

        try {
            app(IrnotiClient::class)->wallet()->balance();
            $this->fail('Expected IrnotiApiException.');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isUnauthenticated());
            $this->assertSame('', $e->errorCode());
            $this->assertSame('Invalid API key', $e->getMessage());
        }
    }

    public function test_rate_limit_and_array_messages(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 429, 'message' => ['Too', 'many']], 429)]);

        try {
            app(IrnotiClient::class)->lines()->list();
            $this->fail('Expected IrnotiApiException.');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame('Too many', $e->getMessage());
        }
    }

    public function test_with_api_key_returns_independent_client(): void
    {
        Http::fake(['*' => Http::response(['balance' => '1840000', 'currency' => 'IRR'])]);

        $client = app(IrnotiClient::class);
        $other = $client->withApiKey('irn_live_other');

        $this->assertNotSame($client, $other);
        $this->assertSame('IRR', $other->wallet()->balance()['currency']);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer irn_live_other'));
    }
}
