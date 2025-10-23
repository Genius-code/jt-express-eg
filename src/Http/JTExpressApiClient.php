<?php

namespace GeniusCode\JTExpressEg\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressApiClient
{
    private const TIMEOUT_SECONDS = 30;
    private const ENDPOINT_CREATE_ORDER = '/webopenplatformapi/api/order/addOrder';

    public function __construct(
        private readonly string $baseUrl
    ) {}

    public function createOrder(string $bizContent, array $headers): Response
    {
        $this->logRequest(self::ENDPOINT_CREATE_ORDER, $bizContent, $headers);

        $response = Http::withHeaders($headers)
            ->asForm()
            ->timeout(self::TIMEOUT_SECONDS)
            ->post($this->baseUrl . self::ENDPOINT_CREATE_ORDER, [
                'bizContent' => $bizContent
            ]);

        $this->logResponse($response);

        return $response;
    }

    private function logRequest(string $endpoint, string $bizContent, array $headers): void
    {
        Log::info('J&T Express API Request', [
            'endpoint' => $this->baseUrl . $endpoint,
            'timestamp' => $headers['timestamp'] ?? null,
            'api_account' => $headers['apiAccount'] ?? null,
            'biz_content_length' => strlen($bizContent)
        ]);
    }

    private function logResponse(Response $response): void
    {
        Log::info('J&T Express API Response', [
            'status' => $response->status(),
            'response' => $response->json()
        ]);
    }
}