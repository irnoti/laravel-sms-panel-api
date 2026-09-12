<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «پیامپ» — the map / postal-code sibling of regional send: target one
 * mobile operator's postal-code bank across one or more map areas, each
 * with its own message count. Requires the `sms.peyamap` ability.
 */
class PeyamapResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/peyamap');
    }

    /**
     * @param  string  $operator  One of the codes from App\Support\SmsAudience::postalOperators() — e.g. "mci", "irancell".
     * @param  array<int, array{name: string, count: int}>  $areas  1-50 entries.
     * @return array<string, mixed>
     */
    public function create(
        string $operator,
        array $areas,
        string $message,
        ?string $from = null,
        ?string $scheduledAt = null,
    ): array {
        return $this->client->request('post', '/sms/peyamap', $this->filter([
            'operator' => $operator,
            'areas' => $areas,
            'message' => $message,
            'from' => $from,
            'scheduled_at' => $scheduledAt,
        ]));
    }

    /**
     * Cancel a not-yet-fired peyamap send and get a full refund.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/peyamap/{$id}");
    }
}
