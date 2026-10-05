<?php

namespace GeniusCode\JTExpressEg\Exceptions;

class ApiException extends JTExpressException
{
    public function __construct(
        string $message,
        public readonly ?string $apiCode = null,
        public readonly int $statusCode = 0,
        public readonly ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(array $response, int $statusCode): self
    {
        return new self(
            message: $response['msg'] ?? 'Unknown API error',
            apiCode: $response['code'] ?? null,
            statusCode: $statusCode,
            responseData: $response
        );
    }
}