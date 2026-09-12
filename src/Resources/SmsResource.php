<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * Plain, immediate sends. Requires the `sms.send` / `sms.bulk` abilities
 * respectively (either on the whole account or scoped onto the access key).
 */
class SmsResource extends Resource
{
    /**
     * Send a single SMS, billed to the wallet immediately.
     *
     * @return array{id: int, status: string, cost_toman: int}
     */
    public function send(string $to, string $message, ?string $from = null): array
    {
        return $this->client->request('post', '/sms/send', $this->filter([
            'to' => $to,
            'message' => $message,
            'from' => $from,
        ]));
    }

    /**
     * Send the same message to up to 200 recipients — a mix of raw numbers
     * and phonebook group ids.
     *
     * @param  array<int, string>  $numbers
     * @param  array<int, int>  $groupIds
     * @return array{message_ids: array<int, int>, recipients: int, cost_toman: int}
     */
    public function bulk(array $numbers, string $message, array $groupIds = [], ?string $from = null): array
    {
        return $this->client->request('post', '/sms/bulk', $this->filter([
            'numbers' => $numbers,
            'group_ids' => $groupIds,
            'message' => $message,
            'from' => $from,
        ]));
    }
}
