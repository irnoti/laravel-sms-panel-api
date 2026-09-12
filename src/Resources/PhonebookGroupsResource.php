<?php

namespace Irnoti\SmsPanelApi\Resources;

class PhonebookGroupsResource extends Resource
{
    /**
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function list(): array
    {
        return $this->client->request('get', '/phonebook/groups');
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, ?string $description = null, bool $showToChild = false): array
    {
        return $this->client->request('post', '/phonebook/groups', $this->filter([
            'name' => $name,
            'description' => $description,
            'show_to_child' => $showToChild,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, string $name, ?string $description = null, bool $showToChild = false): array
    {
        return $this->client->request('put', "/phonebook/groups/{$id}", $this->filter([
            'name' => $name,
            'description' => $description,
            'show_to_child' => $showToChild,
        ]));
    }

    public function delete(int $id): void
    {
        $this->client->request('delete', "/phonebook/groups/{$id}");
    }
}
