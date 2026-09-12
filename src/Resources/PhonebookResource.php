<?php

namespace Irnoti\SmsPanelApi\Resources;

/**
 * Phonebook contacts + groups, two-way synced with your irnoti panel.
 * Gated by the account's `phonebook_enabled` setting rather than an
 * ability — no scope needed on the key itself.
 */
class PhonebookResource extends Resource
{
    public function contacts(): PhonebookContactsResource
    {
        return new PhonebookContactsResource($this->client);
    }

    public function groups(): PhonebookGroupsResource
    {
        return new PhonebookGroupsResource($this->client);
    }
}
