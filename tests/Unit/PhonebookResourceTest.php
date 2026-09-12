<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class PhonebookResourceTest extends TestCase
{
    public function test_create_contact_sends_expected_payload(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/phonebook/contacts' => Http::response([
                'success' => true,
                'data' => ['id' => 7, 'mobile' => '09121234567', 'first_name' => 'Ali'],
            ], 201),
        ]);

        app(IrnotiClient::class)->phonebook()->contacts()->create(
            mobile: '09121234567',
            attributes: ['first_name' => 'Ali'],
            groupIds: [1, 2],
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://irnoti.test/api/v1/panel/phonebook/contacts'
                && $request['mobile'] === '09121234567'
                && $request['first_name'] === 'Ali'
                && $request['group_ids'] === [1, 2];
        });
    }

    public function test_delete_contact_calls_correct_endpoint(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/phonebook/contacts/7' => Http::response(['success' => true, 'data' => []]),
        ]);

        app(IrnotiClient::class)->phonebook()->contacts()->delete(7);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://irnoti.test/api/v1/panel/phonebook/contacts/7'
                && $request->method() === 'DELETE';
        });
    }

    public function test_create_group(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/phonebook/groups' => Http::response([
                'success' => true,
                'data' => ['id' => 3, 'name' => 'VIP customers'],
            ], 201),
        ]);

        $group = app(IrnotiClient::class)->phonebook()->groups()->create('VIP customers');

        $this->assertSame(3, $group['id']);
    }
}
