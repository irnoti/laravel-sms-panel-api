<?php

namespace Irnoti\SmsPanelApi\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Thrown for every non-success response the irnoti panel API returns.
 *
 * The API always answers errors as:
 *
 *   {"success": false, "error": {"code": "...", "message": "...", "details"?: {...}}}
 *
 * `code` is a stable, namespaced, English identifier your code can safely
 * switch on (e.g. "sms.insufficient_balance"); `message` is a human-readable
 * English description; `details` — when present — mirrors Laravel's
 * per-field validation error bag.
 *
 * @see https://irnoti.com/developers
 */
class IrnotiApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $statusCode,
        protected array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json() ?? [];
        $error = $payload['error'] ?? [];

        return new self(
            message: $error['message'] ?? 'The irnoti API returned an unexpected error response.',
            errorCode: $error['code'] ?? 'server.error',
            statusCode: $response->status(),
            details: $error['details'] ?? [],
        );
    }

    /**
     * The stable, namespaced error code, e.g. "sms.insufficient_balance".
     *
     * @see https://irnoti.com/developers for the full code reference.
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * The HTTP status code the API responded with.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Laravel-style validation error bag, only present for `validation.failed`.
     *
     * @return array<string, array<int, string>>
     */
    public function details(): array
    {
        return $this->details;
    }

    /**
     * True when the account's wallet balance is too low to cover the send.
     */
    public function isInsufficientBalance(): bool
    {
        return $this->errorCode === 'sms.insufficient_balance';
    }

    /**
     * True when the request was rejected for hitting the per-key rate limit.
     */
    public function isRateLimited(): bool
    {
        return $this->errorCode === 'rate_limited' || $this->statusCode === 429;
    }

    /**
     * True when the access key itself is missing, invalid, revoked or expired.
     */
    public function isUnauthenticated(): bool
    {
        return in_array($this->errorCode, ['auth.invalid_key', 'auth.unauthenticated'], true);
    }

    /**
     * True when request validation failed; inspect details() for field errors.
     */
    public function isValidationError(): bool
    {
        return $this->errorCode === 'validation.failed';
    }
}
