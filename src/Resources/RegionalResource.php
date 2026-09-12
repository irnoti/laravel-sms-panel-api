<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «ارسال منطقه‌ای» — target a province (and optional city) plus a message
 * count; the provider picks the actual recipient numbers, so there's no
 * recipient list to supply. Requires the `sms.regional` ability.
 */
class RegionalResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/regional');
    }

    /**
     * @return array<string, mixed>
     */
    public function create(
        string $province,
        int $count,
        string $message,
        ?string $city = null,
        ?string $from = null,
        ?string $scheduledAt = null,
    ): array {
        return $this->client->request('post', '/sms/regional', $this->filter([
            'province' => $province,
            'city' => $city,
            'count' => $count,
            'message' => $message,
            'from' => $from,
            'scheduled_at' => $scheduledAt,
        ]));
    }

    /**
     * Cancel a not-yet-fired regional send and get a full refund.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/regional/{$id}");
    }
}
