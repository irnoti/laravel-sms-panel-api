<?php

/**
 * Inside any Laravel app with IRNOTI_API_KEY set.
 */

use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
use Irnoti\SmsPanelApi\Facades\Irnoti;

try {
    $balance = Irnoti::wallet()->balance();               // ['balance' => '1840000', 'currency' => 'IRR']
    $line = Irnoti::lines()->list()[0]['id'];

    $quote = Irnoti::sms()->quote($line, 'Your order has shipped!', '09121234567');

    $sent = Irnoti::sms()->send(
        lineId: $line,
        text: 'Your order has shipped!',
        to: '09121234567',
        idempotencyKey: 'order-1042',                      // safe to retry
    );
    // ['batchId' => '918', 'count' => 1, 'totalPrice' => '980', 'replayed' => false]

    Irnoti::sms()->pattern(code: 'k3f9a2', to: '09121234567', params: ['code' => '4821']);
} catch (IrnotiApiException $e) {
    if ($e->isInsufficientBalance()) {
        // top up the wallet
    }
    report($e);
}
