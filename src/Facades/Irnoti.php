<?php

namespace Irnoti\SmsPanelApi\Facades;

use Illuminate\Support\Facades\Facade;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Resources\BatchesResource;
use Irnoti\SmsPanelApi\Resources\BotsResource;
use Irnoti\SmsPanelApi\Resources\LinesResource;
use Irnoti\SmsPanelApi\Resources\LinkTrackerResource;
use Irnoti\SmsPanelApi\Resources\MessagesResource;
use Irnoti\SmsPanelApi\Resources\NumberBanksResource;
use Irnoti\SmsPanelApi\Resources\PhonebookResource;
use Irnoti\SmsPanelApi\Resources\ServicesResource;
use Irnoti\SmsPanelApi\Resources\SmsResource;
use Irnoti\SmsPanelApi\Resources\VerifyResource;
use Irnoti\SmsPanelApi\Resources\VoipResource;
use Irnoti\SmsPanelApi\Resources\WalletResource;

/**
 * @method static SmsResource sms()
 * @method static MessagesResource messages()
 * @method static BatchesResource batches()
 * @method static LinesResource lines()
 * @method static WalletResource wallet()
 * @method static PhonebookResource phonebook()
 * @method static ServicesResource services()
 * @method static LinkTrackerResource linkTracker()
 * @method static NumberBanksResource numberBanks()
 * @method static VerifyResource verify()
 * @method static BotsResource bots()
 * @method static VoipResource voip()
 * @method static IrnotiClient withApiKey(string $apiKey)
 *
 * @see IrnotiClient
 */
class Irnoti extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IrnotiClient::class;
    }
}
