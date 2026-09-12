<?php

namespace Irnoti\SmsPanelApi\Resources;

class PhonebookContactsResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(?string $keyword = null, ?int $groupId = null, int $perPage = 20): array
    {
        return $this->client->request('get', '/phonebook/contacts', $this->filter([
            'keyword' => $keyword,
            'group_id' => $groupId,
            'per_page' => $perPage,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes  first_name, last_name, email, company, nickname, gender, birth_date, description.
     * @param  array<int, int>  $groupIds
     * @return array<string, mixed>
     */
    public function create(string $mobile, array $attributes = [], array $groupIds = []): array
    {
        return $this->client->request('post', '/phonebook/contacts', $this->filter([
            ...$attributes,
            'mobile' => $mobile,
            'group_ids' => $groupIds,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>|null  $groupIds
     * @return array<string, mixed>
     */
    public function update(int $id, array $attributes = [], ?array $groupIds = null): array
    {
        return $this->client->request('put', "/phonebook/contacts/{$id}", $this->filter([
            ...$attributes,
            'group_ids' => $groupIds,
        ]));
    }

    public function delete(int $id): void
    {
        $this->client->request('delete', "/phonebook/contacts/{$id}");
    }
}
