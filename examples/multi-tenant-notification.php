<?php

/**
 * Multi-tenant example: your app stores one irnoti access key per customer
 * and needs to send from the right key at request time. Bind a client from
 * the container with the customer's own key instead of relying on the
 * single config/env-bound singleton.
 */

use Irnoti\SmsPanelApi\Facades\Irnoti;

function notifyCustomer(string $customerApiKey, string $mobile, string $message): void
{
    $client = Irnoti::withApiKey($customerApiKey);

    $client->sms()->send(to: $mobile, message: $message);
}
