<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * Account-level read-only endpoints — no ability scope required.
 */
class AccountResource extends Resource
{
    /**
     * Wallet balance + upstream panel credit.
     *
     * @return array{wallet_balance_toman: int, panel_credit_sms: int|null, panel_credit_rial: int|null, panel_credit_error: string|null}
     */
    public function credit(): array
    {
        return $this->client->request('get', '/account/credit');
    }

    /**
     * Sender numbers available to this account, and which one is the default.
     *
     * @return array{numbers: array<int, string>, default: string|null}
     */
    public function numbers(): array
    {
        return $this->client->request('get', '/account/numbers');
    }
}
