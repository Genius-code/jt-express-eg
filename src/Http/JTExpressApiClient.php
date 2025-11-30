<?php

namespace GeniusCode\JTExpressEg\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressApiClient
{
    private const TIMEOUT_SECONDS = 30;

    // Endpoints
    private const ENDPOINT_CREATE_ORDER = '/webopenplatformapi/api/order/addOrder';
    private const ENDPOINT_CANCEL_ORDER = '/webopenplatformapi/api/order/cancelOrder';
    private const ENDPOINT_TRACK_ORDER = '/webopenplatformapi/api/logistics/trace';
    private const ENDPOINT_GET_ORDERS = '/webopenplatformapi/api/order/getOrders';
    private const ENDPOINT_PRINT_ORDER = '/webopenplatformapi/api/order/printOrder';

    public function __construct(
        private readonly string $baseUrl
    ) {}

    public function createOrder(string $bizContent, array $headers): Response
    {
        return $this->post(self::ENDPOINT_CREATE_ORDER, $bizContent, $headers);
    }

    public function cancelOrder(string $bizContent, array $headers): Response
    {
        return $this->post(self::ENDPOINT_CANCEL_ORDER, $bizContent, $headers);
    }

    public function trackOrder(string $bizContent, array $headers): Response
    {
        return $this->post(self::ENDPOINT_TRACK_ORDER, $bizContent, $headers);
    }

    public function getOrders(string $bizContent, array $headers): Response
    {
        return $this->post(self::ENDPOINT_GET_ORDERS, $bizContent, $headers);
    }

    public function printOrder(string $bizContent, array $headers): Response
    {
        return $this->post(self::ENDPOINT_PRINT_ORDER, $bizContent, $headers);
    }

    private function post(string $endpoint, string $bizContent, array $headers): Response
    {
        $this->logRequest($endpoint, $bizContent, $headers);

        $response = Http::withHeaders($headers)
            ->asForm()
            ->timeout(self::TIMEOUT_SECONDS)
            ->post($this->baseUrl . $endpoint, [
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