<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure\Exception;

/**
 * Exception thrown when Amazee AI API requests fail.
 */
final class AmazeeAiApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?int $httpStatusCode = null,
        private readonly ?string $responseBody = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code from the failed request.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the response body from the failed request.
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
