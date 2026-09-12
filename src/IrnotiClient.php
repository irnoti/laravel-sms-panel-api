<?php

namespace Irnoti\SmsPanelApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
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
 * The single low-level gateway every resource talks through.
 *
 * You will rarely call {@see request()} directly — reach for the typed
 * resource methods (`$client->sms()->send(...)`, `$client->account()->credit()`, …)
 * instead. This class only knows how to turn a (method, path, payload) tuple
 * into an authenticated HTTP call against `/v1/panel/*` and unwrap the
 * envelope the API always answers with.
 */
class IrnotiClient
{
    private const API_PREFIX = '/v1/panel';

    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected int $timeout = 15,
        protected int $retryTimes = 1,
        protected int $retrySleep = 200,
    ) {}

    /**
     * Build a client from an explicit key, ignoring config/env entirely.
     * Handy for multi-tenant apps that hold one irnoti key per customer.
     */
    public static function withKey(string $apiKey, ?string $baseUrl = null): self
    {
        return new self(
            baseUrl: $baseUrl ?? config('sms-panel-api.base_url', 'https://api.irnoti.com'),
            apiKey: $apiKey,
            timeout: (int) config('sms-panel-api.timeout', 15),
            retryTimes: (int) config('sms-panel-api.retry.times', 1),
            retrySleep: (int) config('sms-panel-api.retry.sleep', 200),
        );
    }

    /**
     * Return a copy of this client bound to a different access key, keeping
     * every other setting (base URL, timeout, retries) unchanged.
     */
    public function withApiKey(string $apiKey): self
    {
        return new self($this->baseUrl, $apiKey, $this->timeout, $this->retryTimes, $this->retrySleep);
    }

    public function account(): AccountResource
    {
        return new AccountResource($this);
    }

    public function messages(): MessagesResource
    {
        return new MessagesResource($this);
    }

    public function sms(): SmsResource
    {
        return new SmsResource($this);
    }

    public function scheduled(): ScheduledResource
    {
        return new ScheduledResource($this);
    }

    public function gradual(): GradualResource
    {
        return new GradualResource($this);
    }

    public function regional(): RegionalResource
    {
        return new RegionalResource($this);
    }

    public function targeted(): TargetedResource
    {
        return new TargetedResource($this);
    }

    public function peyamap(): PeyamapResource
    {
        return new PeyamapResource($this);
    }

    public function smart(): SmartResource
    {
        return new SmartResource($this);
    }

    public function linkTracker(): LinkTrackerResource
    {
        return new LinkTrackerResource($this);
    }

    public function phonebook(): PhonebookResource
    {
        return new PhonebookResource($this);
    }

    /**
     * Perform an authenticated call against `/v1/panel{$path}` and return the
     * decoded `data` payload on success.
     *
     * @param  array<string, mixed>  $payload  Query string for GET, JSON body otherwise.
     * @return array<string, mixed>
     *
     * @throws IrnotiApiException on any non-2xx / `{"success": false}` response.
     */
    public function request(string $method, string $path, array $payload = []): array
    {
        $method = strtolower($method);
        $uri = self::API_PREFIX . $path;

        $response = $method === 'get'
            ? $this->pendingRequest()->get($uri, $payload)
            : $this->pendingRequest()->{$method}($uri, $payload);

        return $this->unwrap($response);
    }

    protected function pendingRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken((string) $this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function unwrap(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            throw IrnotiApiException::fromResponse($response);
        }

        return $payload['data'] ?? [];
    }
}
