<?php

namespace Irnoti\SmsPanelApi\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Facades\Irnoti;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Tests\TestCase;

class ResourcesTest extends TestCase
{
    protected function getPackageAliases($app): array
    {
        return ['Irnoti' => Irnoti::class];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function assertCall(string $method, string $path, ?callable $extra = null): void
    {
        Http::assertSent(fn ($r) => $r->method() === $method
            && str_starts_with($r->url(), 'https://irnoti.test/api/v1' . $path)
            && ($extra === null || $extra($r)));
    }

    public function test_messages_and_batches(): void
    {
        Http::fake(['*' => Http::response([])]);
        $c = app(IrnotiClient::class);

        $c->messages()->list(take: 2, before: 90);
        $this->assertCall('GET', '/sms/messages', fn ($r) => str_contains($r->url(), 'take=2') && str_contains($r->url(), 'before=90'));
        $c->messages()->get(5521);
        $this->assertCall('GET', '/sms/messages/5521');
        $c->messages()->check(5521);
        $this->assertCall('POST', '/sms/messages/5521/check');
        $c->batches()->scheduled();
        $this->assertCall('GET', '/sms/scheduled');
        $c->batches()->cancel(7);
        $this->assertCall('POST', '/sms/batches/7/cancel');
        $c->batches()->pause(7);
        $this->assertCall('POST', '/sms/batches/7/pause');
        $c->batches()->resume(7);
        $this->assertCall('POST', '/sms/batches/7/resume');
        $c->lines()->list();
        $this->assertCall('GET', '/sms/lines');
    }

    public function test_204_responses_return_empty_array(): void
    {
        Http::fake(['*' => Http::response(null, 204)]);

        app(IrnotiClient::class)->batches()->pause(1);
        $this->assertTrue(true);
    }

    public function test_wallet_and_phonebook(): void
    {
        Http::fake(['*' => Http::response([])]);
        $c = app(IrnotiClient::class);

        $c->wallet()->transactions();
        $this->assertCall('GET', '/wallet/transactions');
        $c->phonebook()->groups();
        $this->assertCall('GET', '/phonebook/groups');
        $c->phonebook()->createGroup('VIP');
        $this->assertCall('POST', '/phonebook/groups', fn ($r) => $r['name'] === 'VIP');
        $c->phonebook()->deleteGroup(3);
        $this->assertCall('DELETE', '/phonebook/groups/3');
        $c->phonebook()->contacts(12, take: 10, after: 5, q: 'ali');
        $this->assertCall('GET', '/phonebook/groups/12/contacts', fn ($r) => str_contains($r->url(), 'after=5') && str_contains($r->url(), 'q=ali'));
        $c->phonebook()->addContacts(12, [['mobile' => '09121234567', 'name' => 'Ali']]);
        $this->assertCall('POST', '/phonebook/groups/12/contacts', fn ($r) => $r['contacts'][0]['mobile'] === '09121234567');
        $c->phonebook()->updateContact(9, ['optedOut' => true]);
        $this->assertCall('PATCH', '/phonebook/contacts/9', fn ($r) => $r['optedOut'] === true);
        $c->phonebook()->deleteContact(9);
        $this->assertCall('DELETE', '/phonebook/contacts/9');
    }

    public function test_phonebook_csv_import_is_multipart(): void
    {
        Http::fake(['*' => Http::response(['added' => 1, 'duplicates' => 0, 'invalid' => 0])]);

        $result = app(IrnotiClient::class)->phonebook()->importCsv(12, "09121234567,Ali\n");

        $this->assertSame(1, $result['added']);
        Http::assertSent(fn ($r) => $r->url() === 'https://irnoti.test/api/v1/phonebook/groups/12/import'
            && str_contains($r->header('Content-Type')[0] ?? '', 'multipart/form-data'));
    }

    public function test_services(): void
    {
        Http::fake(['*' => Http::response([])]);
        $s = app(IrnotiClient::class)->services();

        $s->prices();
        $this->assertCall('GET', '/services/prices');
        $s->internationalRates();
        $this->assertCall('GET', '/services/international/rates');
        $s->quote('regional', ['province' => 'tehran', 'count' => 1000, 'text' => 'x']);
        $this->assertCall('POST', '/services/regional/quote');
        $s->smart(['09121234567'], 'hi');
        $this->assertCall('POST', '/services/smart', fn ($r) => $r['recipients'] === ['09121234567']);
        $s->messenger('bale', ['09121234567'], 'hi');
        $this->assertCall('POST', '/services/messenger', fn ($r) => $r['channel'] === 'bale');
        $s->get('voice', 4);
        $this->assertCall('GET', '/services/voice/4');
        $s->refresh('voice', 4);
        $this->assertCall('POST', '/services/voice/4/refresh');
        $s->cancel('voice', 4);
        $this->assertCall('POST', '/services/voice/4/cancel');
    }

    public function test_link_tracker_number_banks_verify_bots_voip(): void
    {
        Http::fake(['*' => Http::response([])]);
        $c = app(IrnotiClient::class);

        $c->linkTracker()->send(1, 'hi', 'https://x.test', to: ['09121234567'], holdoutPercent: 20);
        $this->assertCall('POST', '/link-tracker/send', fn ($r) => $r['url'] === 'https://x.test' && $r['holdoutPercent'] === 20 && ! isset($r['groupIds']));
        $c->linkTracker()->campaign(3);
        $this->assertCall('GET', '/link-tracker/campaigns/3');
        $c->linkTracker()->sendNow(8);
        $this->assertCall('POST', '/link-tracker/holdouts/8/send-now');

        $c->numberBanks()->send(2, 500, 1, 'promo', ['operator' => 'mci'], 'k1');
        $this->assertCall('POST', '/number-banks/send', fn ($r) => $r['bankId'] === '2' && str_contains($r->body(), '"filters":{"operator":"mci"}') && $r->hasHeader('Idempotency-Key', 'k1'));
        $c->numberBanks()->facets(2);
        $this->assertCall('GET', '/number-banks/2/facets');

        $c->verify()->create(to: '09121234567', options: ['length' => 6]);
        $this->assertCall('POST', '/verify/sessions', fn ($r) => $r['to'] === '09121234567' && $r['length'] === 6 && ! isset($r['bot']));
        $c->verify()->cancel(4);
        $this->assertCall('POST', '/verify/sessions/4/cancel');

        $c->bots()->otp(5, ['chatId' => '1'], length: 6);
        $this->assertCall('POST', '/botbuilder/bots/5/otp', fn ($r) => $r['length'] === 6);
        $c->bots()->shopOrders(5, 'new');
        $this->assertCall('GET', '/botbuilder/bots/5/shop/orders', fn ($r) => str_contains($r->url(), 'status=new'));

        $c->voip()->call('09121234567', '09351234567');
        $this->assertCall('POST', '/voip/calls', fn ($r) => $r['from'] === '09121234567');
    }

    public function test_facade_resolves_resources(): void
    {
        Http::fake(['*' => Http::response(['balance' => '1', 'currency' => 'IRR'])]);

        $this->assertSame('IRR', Irnoti::wallet()->balance()['currency']);
    }
}
