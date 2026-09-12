<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «ارسال هوشمند» — deliver to your own recipient list "smart": an internet
 * push notification first, falling back to a billed SMS only for whoever
 * didn't see it. Immediate-only (no schedule, no recall). The
 * smart/sms/failed/pending split is pulled synchronously via refresh(),
 * not a background poll. Requires the `sms.smart` ability.
 */
class SmartResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/smart');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->request('get', "/sms/smart/{$id}");
    }

    /**
     * @param  array<int, string>  $numbers
     * @param  array<int, int>  $groupIds
     * @return array<string, mixed>
     */
    public function create(string $message, array $numbers = [], array $groupIds = [], ?string $from = null): array
    {
        return $this->client->request('post', '/sms/smart', $this->filter([
            'numbers' => $numbers,
            'group_ids' => $groupIds,
            'message' => $message,
            'from' => $from,
        ]));
    }

    /**
     * Pull the latest smart/sms/failed/pending split from the provider.
     * Rate-limited server-side like the dashboard's own refresh action —
     * don't poll this in a tight loop.
     *
     * @return array<string, mixed>
     */
    public function refresh(int $id): array
    {
        return $this->client->request('post', "/sms/smart/{$id}/refresh");
    }
}
