<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * The sent-message archive — always available, no ability scope required.
 */
class MessagesResource extends Resource
{
    /**
     * List sent messages, most recent first.
     *
     * @return array{items: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function list(int $perPage = 20): array
    {
        return $this->client->request('get', '/sms/messages', ['per_page' => $perPage]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->request('get', "/sms/messages/{$id}");
    }
}
