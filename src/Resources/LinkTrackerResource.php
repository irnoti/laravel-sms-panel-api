<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * «لینک ترکر» — your own recipient list + a destination URL + one body;
 * irnoti mints a unique short link per recipient and reports clicks
 * (time, geo, device, referer) back on the campaign. No provider call is
 * involved — delivery runs on irnoti's own side. Immediate-only.
 * Requires the `sms.link_tracker` ability.
 */
class LinkTrackerResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/link-tracker');
    }

    /**
     * Fetch a campaign together with its click analytics: total clicks,
     * CTR, and the top 200 links by click count.
     *
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->request('get', "/sms/link-tracker/{$id}");
    }

    /**
     * @param  string  $targetUrl  Where each recipient's unique short link redirects to.
     * @param  string  $message  Use the literal placeholder `{link}` where the short link should be
     *                           inserted, or omit it to have the link appended to the message.
     * @param  array<int, string>  $numbers
     * @param  array<int, int>  $groupIds
     * @return array<string, mixed>
     */
    public function create(
        string $targetUrl,
        string $message,
        array $numbers = [],
        array $groupIds = [],
        ?string $from = null,
    ): array {
        return $this->client->request('post', '/sms/link-tracker', $this->filter([
            'numbers' => $numbers,
            'group_ids' => $groupIds,
            'target_url' => $targetUrl,
            'message' => $message,
            'from' => $from,
        ]));
    }

    /**
     * Cancel and refund the undelivered portion of a campaign.
     */
    public function cancel(int $id): void
    {
        $this->client->request('delete', "/sms/link-tracker/{$id}");
    }
}
