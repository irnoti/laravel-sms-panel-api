<?php

/**
 * routes/api.php: Route::post('/irnoti/webhook', IrnotiWebhookController::class);
 * Exclude the route from CSRF protection.
 */

use Illuminate\Http\Request;
use Irnoti\SmsPanelApi\Support\Webhook;

class IrnotiWebhookController
{
    public function __invoke(Request $request)
    {
        try {
            // Must be the RAW body, not the decoded JSON.
            $event = Webhook::parse($request->getContent(), (string) $request->header('X-Irnoti-Signature'), config('services.irnoti.webhook_secret'));
        } catch (\InvalidArgumentException) {
            abort(400);
        }

        // Events can repeat: de-duplicate on X-Irnoti-Delivery.
        match ($event['event']) {
            'message.delivered' => logger()->info('delivered', $event['data']),
            'message.failed' => logger()->warning('failed', $event['data']),
            default => null,
        };

        return response()->noContent();
    }
}
