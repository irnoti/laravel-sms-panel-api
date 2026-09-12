<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class AccountResourceTest extends TestCase
{
    public function test_credit_returns_wallet_and_panel_balance(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/account/credit*' => Http::response([
                'success' => true,
                'data' => ['wallet_balance_toman' => 50000, 'panel_credit_sms' => 120, 'panel_credit_rial' => null, 'panel_credit_error' => null],
            ]),
        ]);

        $credit = app(IrnotiClient::class)->account()->credit();

        $this->assertSame(50000, $credit['wallet_balance_toman']);
        $this->assertSame(120, $credit['panel_credit_sms']);
    }

    public function test_numbers_lists_available_senders(): void
    {
        Http::fake([
            'irnoti.test/api/v1/panel/account/numbers*' => Http::response([
                'success' => true,
                'data' => ['numbers' => ['3000123456'], 'default' => '3000123456'],
            ]),
        ]);

        $numbers = app(IrnotiClient::class)->account()->numbers();

        $this->assertSame(['3000123456'], $numbers['numbers']);
        $this->assertSame('3000123456', $numbers['default']);
    }
}
