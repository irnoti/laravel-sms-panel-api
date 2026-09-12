<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «ارسال هدفمند» — the demographic sibling of regional send: target a
 * segment (gender / age range / province+city / operator / job) plus a
 * message count. At least one filter is required. The provider picks the
 * numbers from its own bank. Requires the `sms.targeted` ability.
 */
class TargetedResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/targeted');
    }

    /**
     * @return array<string, mixed>
     */
    public function create(
        int $count,
        string $message,
        ?string $gender = null,
        ?int $ageMin = null,
        ?int $ageMax = null,
        ?string $province = null,
        ?string $city = null,
        ?string $operator = null,
        ?string $job = null,
        ?string $from = null,
        ?string $scheduledAt = null,
    ): array {
        return $this->client->request('post', '/sms/targeted', $this->filter([
            'gender' => $gender,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'province' => $province,
            'city' => $city,
            'operator' => $operator,
            'job' => $job,
            'count' => $count,
            'message' => $message,
            'from' => $from,
            'scheduled_at' => $scheduledAt,
        ]));
    }

    /**
     * Cancel a not-yet-fired targeted send and get a full refund.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/targeted/{$id}");
    }
}
