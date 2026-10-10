<?php

namespace Irnoti\SmsPanelApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Irnoti\SmsPanelApi\Exceptions\IrnotiApiException;
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
 * The single low-level gateway every resource talks through.
 *
 * You will rarely call {@see request()} directly — reach for the typed
 * resource methods (`$client->sms()->send(...)`, `$client->wallet()->balance()`, …).
 * This class only turns a (method, path, payload) tuple into an authenticated
 * call against `{base_url}/v1/*` and decodes the JSON the API answers with.
 */
class IrnotiClient
{
    private const API_PREFIX = '/v1';

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

    public function sms(): SmsResource
    {
        return new SmsResource($this);
    }

    public function messages(): MessagesResource
    {
        return new MessagesResource($this);
    }

    public function batches(): BatchesResource
    {
        return new BatchesResource($this);
    }

    public function lines(): LinesResource
    {
        return new LinesResource($this);
    }

    public function wallet(): WalletResource
    {
        return new WalletResource($this);
    }

    public function phonebook(): PhonebookResource
    {
        return new PhonebookResource($this);
    }

    public function services(): ServicesResource
    {
        return new ServicesResource($this);
    }

    public function linkTracker(): LinkTrackerResource
    {
        return new LinkTrackerResource($this);
    }

    public function numberBanks(): NumberBanksResource
    {
        return new NumberBanksResource($this);
    }

    public function verify(): VerifyResource
    {
        return new VerifyResource($this);
    }

    public function bots(): BotsResource
    {
        return new BotsResource($this);
    }

    public function voip(): VoipResource
    {
        return new VoipResource($this);
    }

    /**
     * Perform an authenticated call against `/v1{$path}` and return the decoded JSON body.
     * Empty (`204`) responses come back as an empty array.
     *
     * @param  array<string, mixed>  $payload  Query string for GET, JSON body otherwise.
     * @param  array<string, string>  $headers  Extra headers, e.g. `Idempotency-Key`.
     * @return array<int|string, mixed>
     *
     * @throws IrnotiApiException on any non-2xx response.
     */
    public function request(string $method, string $path, array $payload = [], array $headers = []): array
    {
        $method = strtolower($method);
        $uri = self::API_PREFIX . $path;
        $pending = $this->pendingRequest()->withHeaders($headers);

        $response = $method === 'get'
            ? $pending->get($uri, $payload)
            : $pending->{$method}($uri, $payload);

        return $this->unwrap($response);
    }

    /**
     * Multipart file upload (e.g. phonebook CSV import).
     *
     * @return array<int|string, mixed>
     */
    public function upload(string $path, string $field, string $contents, string $filename): array
    {
        $response = $this->pendingRequest(json: false)
            ->asMultipart()
            ->attach($field, $contents, $filename)
            ->post(self::API_PREFIX . $path);

        return $this->unwrap($response);
    }

    protected function pendingRequest(bool $json = true): PendingRequest
    {
        $pending = Http::baseUrl($this->baseUrl)
            ->withToken((string) $this->apiKey)
            ->acceptJson();

        // A forced JSON content type would stop Guzzle from adding the multipart boundary.
        return ($json ? $pending->asJson() : $pending)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function unwrap(Response $response): array
    {
        if (! $response->successful()) {
            throw IrnotiApiException::fromResponse($response);
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }
}
