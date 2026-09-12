<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class ScheduledResourceTest extends TestCase
{
    public function test_create_sends_scheduled_at(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/scheduled' => Http::response([
                'success' => true,
                'data' => ['id' => 9, 'status' => 'scheduled'],
            ], 201),
        ]);

        app(IrnotiClient::class)->scheduled()->create(
            scheduledAt: '2026-09-20 10:00:00',
            message: 'reminder',
            numbers: ['09121234567'],
        );

        Http::assertSent(fn ($request) => $request['scheduled_at'] === '2026-09-20 10:00:00');
    }

    public function test_cancel_not_cancelable_maps_to_exception(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/scheduled/9' => Http::response([
                'success' => false,
                'error' => ['code' => 'sms.not_cancelable', 'message' => 'This send can no longer be canceled.'],
            ], 422),
        ]);

        try {
            app(IrnotiClient::class)->scheduled()->cancel(9);
            $this->fail('Expected IrnotiApiException was not thrown.');
        } catch (IrnotiApiException $exception) {
            $this->assertSame('sms.not_cancelable', $exception->errorCode());
            $this->assertFalse($exception->isValidationError());
        }
    }

    public function test_invalid_key_is_reported_as_unauthenticated(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/sms/scheduled' => Http::response([
                'success' => false,
                'error' => ['code' => 'auth.invalid_key', 'message' => 'Invalid, revoked or expired access key.'],
            ], 401),
        ]);

        try {
            app(IrnotiClient::class)->scheduled()->list();
            $this->fail('Expected IrnotiApiException was not thrown.');
        } catch (IrnotiApiException $exception) {
            $this->assertTrue($exception->isUnauthenticated());
        }
    }
}
