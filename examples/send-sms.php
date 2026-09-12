<?php

/**
 * Minimal usage outside of a Laravel app's service container — build the
 * client directly with an access key. Run with:
 *
 *   IRNOTI_API_KEY=irnt_xxx php examples/send-sms.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
use Irnoti\SmsPanelApi\IrnotiClient;

$client = new IrnotiClient(
    baseUrl: 'https://api.irnoti.com',
    apiKey: getenv('IRNOTI_API_KEY') ?: throw new RuntimeException('Set IRNOTI_API_KEY first.'),
);

try {
    $credit = $client->account()->credit();
    printf("Wallet balance: %d Toman\n", $credit['wallet_balance_toman']);

    $result = $client->sms()->send(to: '09121234567', message: 'Hello from the irnoti SDK!');
    printf("Message #%d queued, cost: %d Toman\n", $result['id'], $result['cost_toman']);
} catch (IrnotiApiException $e) {
    if ($e->isInsufficientBalance()) {
        fwrite(STDERR, "Top up your wallet before sending.\n");
    } elseif ($e->isValidationError()) {
        fwrite(STDERR, 'Validation failed: ' . json_encode($e->details()) . "\n");
    } else {
        fwrite(STDERR, "[{$e->errorCode()}] {$e->getMessage()}\n");
    }

    exit(1);
}
