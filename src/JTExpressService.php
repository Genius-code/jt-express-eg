<?php

namespace Appleera1\JtExpressEg;

use Appleera1\JtExpressEg\Builders\OrderRequestBuilder;
use Appleera1\JtExpressEg\Exceptions\InvalidOrderDataException;
use Appleera1\JtExpressEg\Formatters\AddressFormatter;
use Appleera1\JtExpressEg\Formatters\OrderItemFormatter;
use Appleera1\JtExpressEg\Handlers\OrderResponseHandler;
use Appleera1\JtExpressEg\Http\JTExpressApiClient;
use Appleera1\JtExpressEg\Validators\OrderDataValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressService
{
    protected string $apiAccount;
    protected string $privateKey;
    protected string $customerCode;
    protected string $customerPwd;
    protected string $baseUrl;

    private JTExpressApiClient $apiClient;
    private OrderResponseHandler $responseHandler;
    private AddressFormatter $addressFormatter;
    private OrderItemFormatter $itemFormatter;
    private OrderDataValidator $validator;

    public function __construct()
    {
        $this->apiAccount = config('jt-express.apiAccount', '292508153084379141');
        $this->privateKey = config('jt-express.privateKey', 'a0a1047cce70493c9d5d29704f05d0d9');
        $this->customerCode = config('jt-express.customerCode', 'J0086000020');
        $this->customerPwd = config('jt-express.customerPwd', '4AF43B0704D20349725BF0BBB64051BB');

        $this->baseUrl = config('app.env') === 'production'
            ? 'https://openapi.jtjms-eg.com'
            : 'https://demoopenapi.jtjms-eg.com';

        // Initialize dependencies
        $this->apiClient = new JTExpressApiClient($this->baseUrl);
        $this->responseHandler = new OrderResponseHandler();
        $this->addressFormatter = new AddressFormatter();
        $this->itemFormatter = new OrderItemFormatter();
        $this->validator = new OrderDataValidator();
    }

    /**
     * Create a new order
     *
     * @param array $orderData Order data including shippingAddress and orderItems
     * @return array Response data
     */
    public function createOrder(array $orderData): array
    {
        try {
            // Validate order data
            $this->validator->validate($orderData);

            // Generate timestamp
            $timestamp = $this->generateTimestamp();

            // Calculate digests
            $bizContentDigest = $this->calculateBizContentDigest(
                $this->customerCode,
                $this->customerPwd,
                $this->privateKey
            );

            // Format address and items
            $receiver = $this->addressFormatter->formatReceiver($orderData['shippingAddress'] ?? []);
            $sender = $this->addressFormatter->formatSender();
            $items = $this->itemFormatter->format($orderData['orderItems'] ?? []);

            // Build order request
            $builder = new OrderRequestBuilder($this->customerCode, $bizContentDigest);
            $orderRequest = $builder->build($orderData, $receiver, $sender, $items);

            // Prepare request
            $bizContentJson = json_encode($orderRequest->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            // Send request
            $response = $this->apiClient->createOrder($bizContentJson, $headers);

            // Handle response
            return $this->responseHandler->handle($response);

        } catch (InvalidOrderDataException $e) {
            Log::error('J&T Express Create Order Validation Failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 400
            ];

        } catch (\Exception $e) {
            Log::error('J&T Express Create Order Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Cancel an order
     *
     * @param string $txlogisticId Transaction logistic ID
     * @param string $reason Cancellation reason
     * @return array Response data
     */
    public function cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array
    {
        try {
            $timestamp = $this->generateTimestamp();

            $bizContentDigest = $this->calculateBizContentDigest(
                $this->customerCode,
                $this->customerPwd,
                $this->privateKey
            );

            $bizContentArray = [
                'txlogisticId' => $txlogisticId,
                'orderType' => 1,
                'reason' => $reason,
                'customerCode' => $this->customerCode,
                'digest' => $bizContentDigest
            ];

            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            $response = Http::withHeaders($headers)
                ->asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/webopenplatformapi/api/order/cancelOrder', [
                    'bizContent' => $bizContentJson
                ]);

            return $this->responseHandler->handle($response);

        } catch (\Exception $e) {
            Log::error('J&T Express Cancel Order Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Track an order by waybill code
     *
     * @param string $billCode Waybill code
     * @return array Tracking information
     */
    public function trackOrder(string $billCode): array
    {
        try {
            $timestamp = $this->generateTimestamp();

            $bizContentArray = [
                'billCodes' => $billCode
            ];

            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            $response = Http::withHeaders($headers)
                ->asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/webopenplatformapi/api/logistics/trace', [
                    'bizContent' => $bizContentJson
                ]);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['msg'] ?? 'Unknown error',
                'data' => $responseData,
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('J&T Express Track Order Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Get order details by serial numbers
     *
     * @param string|array $serialNumbers Serial number(s)
     * @return array Order details
     */
    public function getOrders(string|array $serialNumbers): array
    {
        try {
            $timestamp = $this->generateTimestamp();

            $bizContentDigest = $this->calculateBizContentDigest(
                $this->customerCode,
                $this->customerPwd,
                $this->privateKey
            );

            $bizContentArray = [
                'command' => 1,
                'serialNumber' => is_array($serialNumbers) ? $serialNumbers : [$serialNumbers],
                'customerCode' => $this->customerCode,
                'digest' => $bizContentDigest
            ];

            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            $response = Http::withHeaders($headers)
                ->asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/webopenplatformapi/api/order/getOrders', [
                    'bizContent' => $bizContentJson
                ]);

            return $this->responseHandler->handle($response);

        } catch (\Exception $e) {
            Log::error('J&T Express Get Orders Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Print order (waybill)
     *
     * @param string $billCode Waybill code
     * @param string $printSize Print size (default: '0')
     * @param int $printCode Print code (default: 0)
     * @return array Print data
     */
    public function printOrder(string $billCode, string $printSize = '0', int $printCode = 0): array
    {
        try {
            $timestamp = $this->generateTimestamp();

            $bizContentArray = [
                'customerCode' => $this->customerCode,
                'digest' => (string)config('jt-express.digest', ''),
                'billCode' => $billCode,
                'printSize' => $printSize,
                'printCode' => $printCode
            ];

            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            $response = Http::withHeaders($headers)
                ->asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/webopenplatformapi/api/order/printOrder', [
                    'bizContent' => $bizContentJson
                ]);

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
                Log::warning("J&T Express Print Order - Illegal parameters: {$billCode}", [
                    'bizContent' => $bizContentJson,
                    'response' => $responseData
                ]);
            } elseif ($errorCode == '121003006') {
                Log::warning("J&T Express Print Order - Order status not printable: {$billCode}");
                $errorMessage = 'Order status does not support printing. Please check if the order has been picked up or is in transit.';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'data' => $responseData,
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('J&T Express Print Order Exception', [
                'message' => $e->getMessage(),
                'bill_code' => $billCode,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    // ---------------------- Protected Helper Methods ----------------------

    /**
     * Calculate signature for bizContent digest
     * sign($CUSTOMER_CODE, $customerPwd, $PRIVATE_KEY)
     */
    protected function calculateBizContentDigest(string $customerCode, string $customerPwd, string $privateKey): string
    {
        $str = $customerCode . $customerPwd . $privateKey;
        return base64_encode(md5($str, true));
    }

    /**
     * Parameter encryption for request header digest
     * encrypt($params, $key)
     */
    protected function calculateHeaderDigest(string $bizContent, string $privateKey): string
    {
        $str = $bizContent . $privateKey;
        return base64_encode(md5($str, true));
    }

    /**
     * Generate request headers
     */
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

    /**
     * Generate timestamp for API requests
     */
    protected function generateTimestamp(): string
    {
        return strval(round(microtime(true) * 1000));
    }
}