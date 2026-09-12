<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «ارسال تدریجی» — drain up to 20,000 recipients in chunks over time.
 * This engine runs on irnoti's own side (not the provider's), so pause/resume
 * work at any point, not just before the first chunk fires.
 * Requires the `sms.gradual` ability.
 */
class GradualResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/gradual');
    }

    /**
     * @param  array<int, string>  $numbers
     * @param  array<int, int>  $groupIds
     * @return array<string, mixed>
     */
    public function create(
        int $chunkSize,
        int $intervalMinutes,
        string $message,
        array $numbers = [],
        array $groupIds = [],
        ?string $from = null,
        ?string $startAt = null,
    ): array {
        return $this->client->request('post', '/sms/gradual', $this->filter([
            'numbers' => $numbers,
            'group_ids' => $groupIds,
            'message' => $message,
            'from' => $from,
            'chunk_size' => $chunkSize,
            'interval_minutes' => $intervalMinutes,
            'start_at' => $startAt,
        ]));
    }

    public function pause(int $id): void
    {
        $this->client->request('post', "/sms/gradual/{$id}/pause");
    }

    public function resume(int $id): void
    {
        $this->client->request('post', "/sms/gradual/{$id}/resume");
    }

    /**
     * Cancel and refund the portion that hasn't been released yet.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/gradual/{$id}");
    }
}
