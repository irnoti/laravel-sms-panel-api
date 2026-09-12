<?php

namespace Irnoti\SmsPanelApi\Facades;

use Illuminate\Support\Facades\Facade;
use Irnoti\SmsPanelApi\IrnotiClient;
use Irnoti\SmsPanelApi\Resources\AccountResource;
use Irnoti\SmsPanelApi\Resources\GradualResource;
use Irnoti\SmsPanelApi\Resources\LinkTrackerResource;
use Irnoti\SmsPanelApi\Resources\MessagesResource;
use Irnoti\SmsPanelApi\Resources\PeyamapResource;
use Irnoti\SmsPanelApi\Resources\PhonebookResource;
use Irnoti\SmsPanelApi\Resources\RegionalResource;
use Irnoti\SmsPanelApi\Resources\ScheduledResource;
use Irnoti\SmsPanelApi\Resources\SmartResource;
use Irnoti\SmsPanelApi\Resources\SmsResource;
use Irnoti\SmsPanelApi\Resources\TargetedResource;

/**
 * @method static AccountResource account()
 * @method static MessagesResource messages()
 * @method static SmsResource sms()
 * @method static ScheduledResource scheduled()
 * @method static GradualResource gradual()
 * @method static RegionalResource regional()
 * @method static TargetedResource targeted()
 * @method static PeyamapResource peyamap()
 * @method static SmartResource smart()
 * @method static LinkTrackerResource linkTracker()
 * @method static PhonebookResource phonebook()
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
