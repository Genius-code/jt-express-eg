<?php

namespace GeniusCode\JTExpressEg;

use GeniusCode\JTExpressEg\Builders\OrderRequestBuilder;
use GeniusCode\JTExpressEg\Exceptions\ApiException;
use GeniusCode\JTExpressEg\Exceptions\InvalidOrderDataException;
use GeniusCode\JTExpressEg\Formatters\AddressFormatter;
use GeniusCode\JTExpressEg\Formatters\OrderItemFormatter;
use GeniusCode\JTExpressEg\Handlers\OrderResponseHandler;
use GeniusCode\JTExpressEg\Http\JTExpressApiClient;
use GeniusCode\JTExpressEg\Validators\OrderDataValidator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class JTExpressService
{
    public function __construct(
        private readonly JTExpressApiClient $apiClient,
        private readonly OrderResponseHandler $responseHandler,
        private readonly AddressFormatter $addressFormatter,
        private readonly OrderItemFormatter $itemFormatter,
        private readonly OrderDataValidator $validator,
        private readonly OrderRequestBuilder $orderRequestBuilder,
        protected readonly string $apiAccount,
        protected readonly string $privateKey,
        protected readonly string $customerCode,
        protected readonly string $customerPwd,
    ) {
    }

    /**
     * Create a new order.
     *
     * @param array $orderData
     * @return array
     * @throws ApiException
     */
    public function createOrder(array $orderData): array
    {
        $this->validator->validate($orderData);

        $bizContentDigest = $this->calculateBizContentDigest(
            $this->customerCode,
            $this->customerPwd,
            $this->privateKey
        );

        $receiver = $this->addressFormatter->formatReceiver($orderData['shippingAddress'] ?? []);
        $sender = $this->addressFormatter->formatSender();
        $items = $this->itemFormatter->format($orderData['orderItems'] ?? []);

        $orderRequest = $this->orderRequestBuilder->build(
            $this->customerCode,
            $bizContentDigest,
            $orderData,
            $receiver,
            $sender,
            $items
        );

        return $this->sendRequest('createOrder', $orderRequest->toArray());
    }

    /**
     * Update an existing order.
     *
     * @param array $orderData
     * @return array
     * @throws ApiException|InvalidOrderDataException
     */
    public function updateOrder(array $orderData): array
    {
        $orderData['operateType'] = 2; // Set operateType to 2 for updating order

        $this->validator->validate($orderData);

        $bizContentDigest = $this->calculateBizContentDigest(
            $this->customerCode,
            $this->customerPwd,
            $this->privateKey
        );

        $receiver = $this->addressFormatter->formatReceiver($orderData['shippingAddress'] ?? []);
        $sender = $this->addressFormatter->formatSender();
        $items = $this->itemFormatter->format($orderData['orderItems'] ?? []);

        $orderRequest = $this->orderRequestBuilder->build(
            $this->customerCode,
            $bizContentDigest,
            $orderData,
            $receiver,
            $sender,
            $items
        );

        return $this->sendRequest('createOrder', $orderRequest->toArray());
    }

    /**
     * Cancel an order.
     *
     * @param string $txlogisticId
     * @param string $reason
     * @return array
     * @throws ApiException
     */
    public function cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array
    {
        $bizContentArray = [
            'txlogisticId' => $txlogisticId,
            'orderType' => 1,
            'reason' => $reason,
            'customerCode' => $this->customerCode,
            'digest' => $this->calculateBizContentDigest($this->customerCode, $this->customerPwd, $this->privateKey),
        ];

        return $this->sendRequest('cancelOrder', $bizContentArray);
    }

    /**
     * Track an order by waybill code.
     *
     * @param string $billCode
     * @return array
     * @throws ApiException
     */
    public function trackOrder(string $billCode): array
    {
        $bizContentArray = ['billCodes' => $billCode];

        return $this->sendRequest('trackOrder', $bizContentArray, function (Response $response) {
            $responseData = $response->json();
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $response->status()
                ];
            }
            throw new ApiException(
                $responseData['msg'] ?? 'Unknown error while tracking order',
                $response->status(),
                $responseData
            );
        });
    }

    /**
     * Get order details by serial numbers.
     *
     * @param string|array $serialNumbers
     * @return array
     * @throws ApiException
     */
    public function getOrders(string|array $serialNumbers): array
    {
        $bizContentArray = [
            'command' => 1,
            'serialNumber' => is_array($serialNumbers) ? $serialNumbers : [$serialNumbers],
            'customerCode' => $this->customerCode,
            'digest' => $this->calculateBizContentDigest($this->customerCode, $this->customerPwd, $this->privateKey),
        ];

        return $this->sendRequest('getOrders', $bizContentArray);
    }

    /**
     * Print order (waybill).
     *
     * @param string $billCode
     * @param string $printSize
     * @param int $printCode
     * @return array
     * @throws ApiException
     */
    public function printOrder(string $billCode, string $printSize = '0', int $printCode = 0): array
    {
        $bizContentArray = [
            'customerCode' => $this->customerCode,
            'digest' => (string)config('jt-express.digest', ''),
            'billCode' => $billCode,
            'printSize' => $printSize,
            'printCode' => $printCode,
        ];

        return $this->sendRequest('printOrder', $bizContentArray, function (Response $response) use ($bizContentArray) {
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] == '1') {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                    'message' => $responseData['msg'] ?? 'Print successful',
                    'status_code' => $response->status()
                ];
            }

            $errorMessage = $responseData['msg'] ?? 'Unknown error';
            $errorCode = $responseData['code'] ?? null;

            if ($errorCode == '145003050') {
                Log::warning("J&T Express Print Order - Illegal parameters: {$bizContentArray['billCode']}", [
                    'bizContent' => $bizContentArray,
                    'response' => $responseData
                ]);
            } elseif ($errorCode == '121003006') {
                Log::warning("J&T Express Print Order - Order status not printable: {$bizContentArray['billCode']}");
                $errorMessage = 'Order status does not support printing. Please check if the order has been picked up or is in transit.';
            }

            throw new ApiException($errorMessage, $response->status(), $responseData, (int) $errorCode);
        });
    }

    /**
     * Prepares and sends an API request, handling the common logic for authentication and response processing.
     *
     * @param string $apiClientMethod The method to call on the JTExpressApiClient.
     * @param array $bizContentArray The business content for the request.
     * @param callable|null $responseHandlerOptional Optional custom response handler.
     * @return array The successful response data.
     * @throws ApiException If the API returns an error.
     */
    private function sendRequest(string $apiClientMethod, array $bizContentArray, ?callable $responseHandlerOptional = null): array
    {
        try {
            $timestamp = $this->generateTimestamp();
            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            /** @var Response $response */
            $response = $this->apiClient->{$apiClientMethod}($bizContentJson, $headers);

            if ($responseHandlerOptional) {
                return $responseHandlerOptional($response);
            }

            return $this->responseHandler->handleWithException($response);

        } catch (\Exception $e) {
            if ($e instanceof ApiException) {
                throw $e;
            }

            Log::error("J&T Express Service Exception in {$apiClientMethod}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw as a generic ApiException to standardize client-facing errors
            throw new ApiException(
                "An unexpected error occurred: " . $e->getMessage(),
                500,
                null,
                $e->getCode(),
                $e
            );
        }
    }

    // ---------------------- Protected Helper Methods ----------------------

    protected function calculateBizContentDigest(string $customerCode, string $customerPwd, string $privateKey): string
    {
        $str = $customerCode . $customerPwd . $privateKey;
        return base64_encode(md5($str, true));
    }

    protected function calculateHeaderDigest(string $bizContent, string $privateKey): string
    {
        $str = $bizContent . $privateKey;
        return base64_encode(md5($str, true));
    }

    protected function getHeaders(string $digest, string $timestamp): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'apiAccount' => $this->apiAccount,
            'digest' => $digest,
            'timestamp' => $timestamp,
        ];
    }

    protected function generateTimestamp(): string
    {
        return strval(round(microtime(true) * 1000));
    }
}