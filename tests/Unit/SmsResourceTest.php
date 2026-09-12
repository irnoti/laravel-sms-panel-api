<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class SmsResourceTest extends TestCase
{
    public function test_send_returns_decoded_data_on_success(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/send' => Http::response([
                'success' => true,
                'data' => ['id' => 42, 'status' => 'queued', 'cost_toman' => 150],
            ], 201),
        ]);

        $result = app(IrnotiClient::class)->sms()->send('09121234567', 'hello');

        $this->assertSame(['id' => 42, 'status' => 'queued', 'cost_toman' => 150], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://irnoti.test/api/v1/panel/sms/send'
                && $request->method() === 'POST'
                && $request['to'] === '09121234567'
                && $request['message'] === 'hello'
                && ! array_key_exists('from', $request->data())
                && $request->hasHeader('Authorization', 'Bearer irnt_test_key');
        });
    }

    public function test_insufficient_balance_throws_typed_exception(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/send' => Http::response([
                'success' => false,
                'error' => ['code' => 'sms.insufficient_balance', 'message' => 'Wallet balance is insufficient.'],
            ], 402),
        ]);

        try {
            app(IrnotiClient::class)->sms()->send('09121234567', 'hello');
            $this->fail('Expected IrnotiApiException was not thrown.');
        } catch (IrnotiApiException $exception) {
            $this->assertTrue($exception->isInsufficientBalance());
            $this->assertSame(402, $exception->statusCode());
            $this->assertSame('sms.insufficient_balance', $exception->errorCode());
        }
    }

    public function test_validation_error_exposes_field_details(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/bulk' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'validation.failed',
                    'message' => 'The given data was invalid.',
                    'details' => ['message' => ['The message field is required.']],
                ],
            ], 422),
        ]);

        try {
            app(IrnotiClient::class)->sms()->bulk(['09121234567'], '');
            $this->fail('Expected IrnotiApiException was not thrown.');
        } catch (IrnotiApiException $exception) {
            $this->assertTrue($exception->isValidationError());
            $this->assertArrayHasKey('message', $exception->details());
        }
    }

    public function test_bulk_sends_numbers_and_group_ids(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/bulk' => Http::response([
                'success' => true,
                'data' => ['message_ids' => [1, 2], 'recipients' => 2, 'cost_toman' => 300],
            ], 201),
        ]);

        app(IrnotiClient::class)->sms()->bulk(['09121234567'], 'hi', groupIds: [5]);

        Http::assertSent(function ($request) {
            return $request['numbers'] === ['09121234567']
                && $request['group_ids'] === [5];
        });
    }
}
