<?php

namespace Appleera1\JtExpressEg;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JTExpressService
{
    protected mixed $apiAccount;
    protected mixed $privateKey;
    protected mixed $customerCode;
    protected mixed $customerPwd;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiAccount   = config('jt-express.apiAccount', '292508153084379141');
        $this->privateKey   = config('jt-express.privateKey', 'a0a1047cce70493c9d5d29704f05d0d9');
        $this->customerCode = config('jt-express.customerCode', 'J0086000020');
        $this->customerPwd  = config('jt-express.customerPwd', '4AF43B0704D20349725BF0BBB64051BB');

        $this->baseUrl = config('app.env') === 'production'
            ? 'https://openapi.jtjms-eg.com'
            : 'https://demoopenapi.jtjms-eg.com';
    }

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
     * Create a new order
     */
    public function createOrder($orderData)
    {
        try {
            $timestamp = strval(round(microtime(true) * 1000));

            $bizContentDigest = $this->calculateBizContentDigest(
                $this->customerCode,
                $this->customerPwd,
                $this->privateKey
            );

            $txlogisticId = $orderData['id'] ?? 'ORDER' . str_pad((string)rand(0, 9999999999), 10, "0", STR_PAD_LEFT);

            $bizContentArray = [
                'customerCode' => $this->customerCode,
                'digest' => $bizContentDigest,
                'deliveryType' => $orderData['deliveryType'] ?? '04',
                'payType' => $orderData['payType'] ?? 'PP_PM',
                'expressType' => $orderData['expressType'] ?? 'EZ',
                'network' => $orderData['network'] ?? '',
                'length' => (float)($orderData['length'] ?? 0),
                'width' => (float)($orderData['width'] ?? 10),
                'height' => (float)($orderData['height'] ?? 0),
                'weight' => (float)($orderData['weight'] ?? 1),
                'sendStartTime' => $orderData['sendStartTime'] ?? Carbon::now()->format('Y-m-d H:i:s'),
                'sendEndTime' => $orderData['sendEndTime'] ?? Carbon::now()->addDay()->format('Y-m-d H:i:s'),
                'itemsValue' => (string)($orderData['total'] ?? ''),
                'remark' => $orderData['remark'] ?? '',
                'invoceNumber' => $orderData['invoiceNumber'] ?? '',
                'packingNumber' => $orderData['packingNumber'] ?? '',
                'batchNumber' => $orderData['batchNumber'] ?? '',
                'txlogisticId' => $txlogisticId,
                'billCode' => $orderData['billCode'] ?? '',
                'operateType' => (int)($orderData['operateType'] ?? 1),
                'orderType' => (string)($orderData['orderType'] ?? '1'),
                'serviceType' => $orderData['serviceType'] ?? '01',
                'expectDeliveryStartTime' => $orderData['expectDeliveryStartTime'] ?? '',
                'expectDeliveryEndTime' => $orderData['expectDeliveryEndTime'] ?? '',
                'goodsType' => $orderData['goodsType'] ?? 'ITN1',
                'totalQuantity' => (string)($orderData['totalQuantity'] ?? '1'),
                'offerFee' => (string)($orderData['offerFee'] ?? '0'),
                'priceCurrency' => 'EGP',
                'receiver' => $this->formatReceiverData($orderData['shippingAddress'] ?? []),
                'sender' => $this->formatSenderData(),
                'items' => $this->formatItems($orderData['orderItems'] ?? []),
            ];

            $bizContentArray = array_filter($bizContentArray, fn($value) => $value !== '' && $value !== null);

            $bizContentJson = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headerDigest = $this->calculateHeaderDigest($bizContentJson, $this->privateKey);
            $headers = $this->getHeaders($headerDigest, $timestamp);

            Log::info('J&T Express Create Order Request', [
                'url' => $this->baseUrl . '/webopenplatformapi/api/order/addOrder',
                'timestamp' => $timestamp,
                'api_account' => $this->apiAccount,
                'customer_code' => $this->customerCode,
                'header_digest' => $headerDigest,
                'biz_content_digest' => $bizContentDigest,
                'tx_logistic_id' => $txlogisticId,
                'biz_content_length' => strlen($bizContentJson)
            ]);

            $response = Http::withHeaders($headers)
                ->asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/webopenplatformapi/api/order/addOrder', [
                    'bizContent' => $bizContentJson
                ]);

            $responseData = $response->json();

            Log::info('J&T Express Create Order Response', [
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] == '1') {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $response->status(),
                    'waybill_code' => $responseData['data']['billCode'] ?? null,
                    'tx_logistic_id' => $responseData['data']['txlogisticId'] ?? $txlogisticId,
                    'sorting_code' => $responseData['data']['sortingCode'] ?? null,
                    'last_center_name' => $responseData['data']['lastCenterName'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['msg'] ?? 'Unknown error',
                'code' => $responseData['code'] ?? null,
                'data' => $responseData,
                'status_code' => $response->status()
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
     */
    public function cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array
    {
        try {
            $timestamp = strval(round(microtime(true) * 1000));

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

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] == '1') {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['msg'] ?? 'Unknown error',
                'code' => $responseData['code'] ?? null,
                'data' => $responseData,
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('J&T Express Cancel Order Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Track an order by waybill code
     */
    public function trackOrder(string $billCode): array
    {
        try {
            $timestamp = strval(round(microtime(true) * 1000));

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
            Log::error('J&T Express Track Order Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrders($serialNumbers): array
    {
        try {
            $timestamp = strval(round(microtime(true) * 1000));

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

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] == '1') {
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
            Log::error('J&T Express Get Orders Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Print order (waybill)
     */
    public function printOrder(string $billCode, string $printSize = '0', int $printCode = 0)
    {
        try {
            $timestamp = strval(round(microtime(true) * 1000));

            $bizContentArray = [
                'customerCode' => $this->customerCode,
                // some implementations use a precomputed digest for print; expose via config
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
            Log::error('J&T Express Print Order Exception: ' . $e->getMessage(), [
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

    /** ---------------------- Helpers ---------------------- */

    protected function formatReceiverData($shippingAddress): array
    {
        if (empty($shippingAddress)) {
            return [
                'name' => 'Test Receiver',
                'mobile' => '01000000000',
                'phone' => '01000000000',
                'countryCode' => 'EGY',
                'prov' => 'القاهرة',
                'city' => 'مدينة نصر',
                'area' => 'test area',
                'street' => 'test street',
                'building' => '',
                'floor' => '',
                'flats' => '',
                'company' => '',
                'mailBox' => '',
                'postCode' => '',
                'latitude' => '',
                'longitude' => ''
            ];
        }

        if (is_object($shippingAddress)) {
            return [
                'name' => trim(($shippingAddress->first_name ?? '') . ' ' . ($shippingAddress->last_name ?? '')),
                'mobile' => $shippingAddress->phone ?? '01000000000',
                'phone' => $shippingAddress->phone ?? '01000000000',
                'countryCode' => 'EGY',
                'prov' => $shippingAddress->state->name ?? $shippingAddress->city->name ?? 'القاهرة',
                'city' => $shippingAddress->city->name ?? 'مدينة نصر',
                'area' => $shippingAddress->area ?? $shippingAddress->state->name ?? '',
                'street' => $shippingAddress->street ?? $shippingAddress->address_line1 ?? '',
                'building' => $shippingAddress->building ?? '',
                'floor' => $shippingAddress->floor ?? '',
                'flats' => $shippingAddress->flats ?? '',
                'company' => $shippingAddress->company ?? '',
                'mailBox' => $shippingAddress->user->email ?? '',
                'postCode' => $shippingAddress->post_code ?? '',
                'latitude' => $shippingAddress->latitude ?? '',
                'longitude' => $shippingAddress->longitude ?? ''
            ];
        }

        return [
            'name' => trim(($shippingAddress['first_name'] ?? '') . ' ' . ($shippingAddress['last_name'] ?? '')),
            'mobile' => $shippingAddress['phone'] ?? '01000000000',
            'phone' => $shippingAddress['phone'] ?? '01000000000',
            'countryCode' => 'EGY',
            'prov' => $shippingAddress['state']['name'] ?? $shippingAddress['city']['name'] ?? 'القاهرة',
            'city' => $shippingAddress['city']['name'] ?? 'مدينة نصر',
            'area' => $shippingAddress['area'] ?? $shippingAddress['state']['name'] ?? '',
            'street' => $shippingAddress['street'] ?? $shippingAddress['address_line1'] ?? '',
            'building' => $shippingAddress['building'] ?? '',
            'floor' => $shippingAddress['floor'] ?? '',
            'flats' => $shippingAddress['flats'] ?? '',
            'company' => $shippingAddress['company'] ?? '',
            'mailBox' => $shippingAddress['user']['email'] ?? '',
            'postCode' => $shippingAddress['post_code'] ?? '',
            'latitude' => $shippingAddress['latitude'] ?? '',
            'longitude' => $shippingAddress['longitude'] ?? ''
        ];
    }

    protected function formatSenderData(): array
    {
        return [
            'name' => config('jt-express.sender.name', 'Test Sender'),
            'mobile' => config('jt-express.sender.mobile', '01000000000'),
            'phone' => config('jt-express.sender.phone', '01000000000'),
            'countryCode' => 'EGY',
            'prov' => config('jt-express.sender.prov', 'الجيزة'),
            'city' => config('jt-express.sender.city', 'مدينة السادس من أكتوبر'),
            'area' => config('jt-express.sender.area', 'test area'),
            'street' => config('jt-express.sender.street', '456'),
            'building' => config('jt-express.sender.building', '1'),
            'floor' => config('jt-express.sender.floor', '22'),
            'flats' => config('jt-express.sender.flats', '33'),
            'company' => config('jt-express.sender.company', 'testCompany'),
            'mailBox' => config('jt-express.sender.mailBox', ''),
            'postCode' => config('jt-express.sender.postCode', ''),
            'latitude' => config('jt-express.sender.latitude', ''),
            'longitude' => config('jt-express.sender.longitude', '')
        ];
    }

    protected function formatItems(iterable $items): array
    {
        $formattedItems = [];

        foreach ($items as $item) {
            if (is_object($item)) {
                $formattedItems[] = [
                    'itemName' => $item->product->name ?? 'Product',
                    'englishName' => method_exists($item->product, 'getTranslation')
                        ? ($item->product->getTranslation('name', 'en') ?? '')
                        : '',
                    'chineseName' => '',
                    'number' => (int)($item->quantity ?? 1),
                    'itemType' => 'ITN1',
                    'priceCurrency' => 'EGP',
                    'itemValue' => (string)($item->price_at_purchase ?? '0'),
                    'itemUrl' => '',
                    'desc' => $item->product->description ?? 'Order Item'
                ];
            } else {
                $formattedItems[] = [
                    'itemName' => $item['product']['name'] ?? 'Product',
                    'englishName' => '',
                    'chineseName' => '',
                    'number' => (int)($item['quantity'] ?? 1),
                    'itemType' => 'ITN1',
                    'priceCurrency' => 'EGP',
                    'itemValue' => (string)($item['price_at_purchase'] ?? '0'),
                    'itemUrl' => '',
                    'desc' => $item['product']['description'] ?? 'Order Item'
                ];
            }
        }

        if (empty($formattedItems)) {
            $formattedItems[] = [
                'itemName' => 'Product',
                'englishName' => '',
                'chineseName' => '',
                'number' => 1,
                'itemType' => 'ITN1',
                'priceCurrency' => 'EGP',
                'itemValue' => '0',
                'itemUrl' => '',
                'desc' => 'Order Item'
            ];
        }

        return $formattedItems;
    }
}