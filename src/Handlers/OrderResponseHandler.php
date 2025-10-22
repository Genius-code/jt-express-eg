<?php

namespace Appleera1\JtExpressEg\Handlers;

use Appleera1\JtExpressEg\Exceptions\ApiException;
use Illuminate\Http\Client\Response;

class OrderResponseHandler
{
    private const SUCCESS_CODE = '1';

    public function handle(Response $response): array
    {
        $data = $response->json();

        if ($this->isSuccessful($response, $data)) {
            return $this->buildSuccessResponse($data, $response->status());
        }

        return $this->buildErrorResponse($data, $response->status());
    }

    public function handleWithException(Response $response): array
    {
        $data = $response->json();

        if ($this->isSuccessful($response, $data)) {
            return $this->buildSuccessResponse($data, $response->status());
        }

        throw ApiException::fromResponse($data, $response->status());
    }

    private function isSuccessful(Response $response, ?array $data): bool
    {
        return $response->successful() &&
               isset($data['code']) &&
               $data['code'] == self::SUCCESS_CODE;
    }

    private function buildSuccessResponse(array $data, int $statusCode): array
    {
        return [
            'success' => true,
            'data' => $data,
            'status_code' => $statusCode,
            'waybill_code' => $data['data']['billCode'] ?? null,
            'tx_logistic_id' => $data['data']['txlogisticId'] ?? null,
            'sorting_code' => $data['data']['sortingCode'] ?? null,
            'last_center_name' => $data['data']['lastCenterName'] ?? null,
        ];
    }

    private function buildErrorResponse(array $data, int $statusCode): array
    {
        return [
            'success' => false,
            'error' => $data['msg'] ?? 'Unknown error',
            'code' => $data['code'] ?? null,
            'data' => $data,
            'status_code' => $statusCode
        ];
    }
}