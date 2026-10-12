<?php

/**
 * One irnoti key per customer: bind a client at request time.
 * Use an irn_test_… key with lineId "sandbox" to try it without cost.
 */

use Irnoti\SmsPanelApi\Facades\Irnoti;

$client = Irnoti::withApiKey($customer->irnoti_api_key);

$client->sms()->send(lineId: $customer->irnoti_line_id, text: 'Welcome aboard!', to: $customer->mobile);
