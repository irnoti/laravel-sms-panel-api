<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «پیامک زماندار» — schedule a send for a future time. The clock is kept by
 * the SMS provider itself, not irnoti, so cancel() only works before it fires.
 * Requires the `scheduled.sms` ability.
 */
class ScheduledResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/scheduled');
    }

    /**
     * @param  array<int, string>  $numbers
     * @param  array<int, int>  $groupIds
     * @return array<string, mixed>
     */
    public function create(
        string $scheduledAt,
        string $message,
        array $numbers = [],
        array $groupIds = [],
        ?string $from = null,
    ): array {
        return $this->client->request('post', '/sms/scheduled', $this->filter([
            'numbers' => $numbers,
            'group_ids' => $groupIds,
            'message' => $message,
            'from' => $from,
            'scheduled_at' => $scheduledAt,
        ]));
    }

    /**
     * Cancel a not-yet-fired scheduled send and get a full refund.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/scheduled/{$id}");
    }
}
