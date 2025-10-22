<?php

namespace Appleera1\JtExpressEg\Exceptions;

class ApiException extends JTExpressException
{
    public function __construct(
        string $message,
        public readonly ?string $apiCode = null,
        public readonly int $statusCode = 0,
        public readonly ?array $responseData = null
    ) {
        parent::__construct($message, $statusCode);
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