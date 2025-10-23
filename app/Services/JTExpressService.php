<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ShippingServiceInterface;
use App\Exceptions\InvalidShippingOrderException;
use App\Exceptions\ShippingServiceException;
use App\Models\Order;
use GeniusCode\JTExpressEg\Exceptions\InvalidOrderDataException;
use GeniusCode\JTExpressEg\Exceptions\JTExpressException;
use GeniusCode\JTExpressEg\JTExpressService as JTExpressClient;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper service for J&T Express Egypt API
 *
 * Provides a simplified interface for creating, tracking, and managing
 * shipping orders through J&T Express with support for both Order models
 * and raw array data.
 *
 * @see https://github.com/genius-code/jt-express-eg
 */
class JTExpressService implements ShippingServiceInterface
{
    private const DEFAULT_COUNTRY_CODE = 'EG';
    private const DEFAULT_CANCEL_REASON = 'Customer request';
    private const DEFAULT_PRINT_SIZE = '0';
    private const DEFAULT_PRINT_CODE = 0;

    public function __construct(
        private readonly JTExpressClient $jtExpressClient
    ) {}

    /**
     * Create a new J&T Express shipping order
     *
     * @param array|Order $orderData Order model or array containing shipping details
     * @return array Response from J&T Express API
     * @throws InvalidShippingOrderException When order data is invalid
     * @throws ShippingServiceException When API communication fails
     *
     * @example
     * // With Order model:
     * $order = Order::with(['shippingAddress.city', 'shippingAddress.state', 'orderItems.product'])->find(1);
     * $response = $service->createOrder($order);
     *
     * // With array:
     * $response = $service->createOrder([
     *     'id' => 'ORDER-123',
     *     'total' => 500,
     *     'shippingAddress' => [...],
     *     'orderItems' => [...]
     * ]);
     */
    public function createOrder(array|Order $orderData): array
    {
        try {
            $orderArray = $orderData instanceof Order
                ? $this->convertOrderToArray($orderData)
                : $orderData;

            Log::info('JTExpress: Creating order', [
                'order_id' => $orderData instanceof Order ? $orderData->id : ($orderData['id'] ?? 'unknown'),
            ]);

            $response = $this->jtExpressClient->createOrder($orderArray);

            if (!($response['success'] ?? false)) {
                Log::warning('JTExpress: Order creation failed', [
                    'order_id' => $orderData instanceof Order ? $orderData->id : ($orderData['id'] ?? 'unknown'),
                    'error' => $response['error'] ?? 'Unknown error',
                    'status_code' => $response['status_code'] ?? null,
                ]);
            } else {
                Log::info('JTExpress: Order created successfully', [
                    'order_id' => $orderData instanceof Order ? $orderData->id : ($orderData['id'] ?? 'unknown'),
                    'txlogistic_id' => $response['data']['txlogisticId'] ?? null,
                    'bill_code' => $response['data']['billCode'] ?? null,
                ]);
            }

            return $response;

        } catch (InvalidOrderDataException $e) {
            Log::error('JTExpress: Invalid order data in wrapper', [
                'message' => $e->getMessage(),
                'order_id' => $orderData instanceof Order ? $orderData->id : null,
            ]);

            throw new InvalidShippingOrderException(
                "Invalid order data: {$e->getMessage()}",
                previous: $e
            );

        } catch (JTExpressException $e) {
            Log::error('JTExpress: API exception in wrapper', [
                'message' => $e->getMessage(),
                'order_id' => $orderData instanceof Order ? $orderData->id : null,
            ]);

            throw ShippingServiceException::orderCreationFailed(
                $e->getMessage(),
                $e
            );

        } catch (InvalidShippingOrderException $e) {
            // Re-throw our own validation exceptions
            throw $e;

        } catch (\Throwable $e) {
            Log::error('JTExpress: Unexpected error in wrapper', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw ShippingServiceException::orderCreationFailed(
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Cancel a J&T Express order
     *
     * @param string $txlogisticId Transaction logistics ID from J&T Express
     * @param string $reason Cancellation reason (optional)
     * @return array Response from J&T Express API
     * @throws ShippingServiceException When cancellation fails
     */
    public function cancelOrder(
        string $txlogisticId,
        string $reason = self::DEFAULT_CANCEL_REASON
    ): array {
        Log::info('JTExpress: Cancelling order', [
            'txlogistic_id' => $txlogisticId,
            'reason' => $reason,
        ]);

        try {
            $response = $this->jtExpressClient->cancelOrder($txlogisticId, $reason);

            if ($response['success'] ?? false) {
                Log::info('JTExpress: Order cancelled successfully', [
                    'txlogistic_id' => $txlogisticId,
                ]);
            } else {
                Log::warning('JTExpress: Order cancellation failed', [
                    'txlogistic_id' => $txlogisticId,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
            }

            return $response;

        } catch (\Throwable $e) {
            Log::error('JTExpress: Cancel order failed', [
                'txlogistic_id' => $txlogisticId,
                'error' => $e->getMessage(),
            ]);

            throw ShippingServiceException::orderCancellationFailed(
                $txlogisticId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Track a J&T Express order by waybill code
     *
     * @param string $billCode Waybill/tracking code
     * @return array Tracking information
     * @throws ShippingServiceException When tracking fails
     */
    public function trackOrder(string $billCode): array
    {
        Log::info('JTExpress: Tracking order', [
            'bill_code' => $billCode,
        ]);

        try {
            return $this->jtExpressClient->trackOrder($billCode);

        } catch (\Throwable $e) {
            Log::error('JTExpress: Track order failed', [
                'bill_code' => $billCode,
                'error' => $e->getMessage(),
            ]);

            throw ShippingServiceException::trackingFailed(
                $billCode,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get order details by serial number(s)
     *
     * @param string|array<string> $serialNumbers One or more serial numbers
     * @return array Order details from J&T Express
     * @throws ShippingServiceException When retrieval fails
     */
    public function getOrders(string|array $serialNumbers): array
    {
        Log::info('JTExpress: Getting order details', [
            'serial_numbers' => $serialNumbers,
        ]);

        try {
            return $this->jtExpressClient->getOrders($serialNumbers);

        } catch (\Throwable $e) {
            Log::error('JTExpress: Get orders failed', [
                'serial_numbers' => $serialNumbers,
                'error' => $e->getMessage(),
            ]);

            throw ShippingServiceException::orderRetrievalFailed(
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Generate printable waybill for an order
     *
     * @param string $billCode Waybill code
     * @param string $printSize Print size code (default: '0')
     * @param int $printCode Print format code (default: 0)
     * @return array Print data (usually base64 encoded PDF)
     * @throws ShippingServiceException When print generation fails
     */
    public function printOrder(
        string $billCode,
        string $printSize = self::DEFAULT_PRINT_SIZE,
        int $printCode = self::DEFAULT_PRINT_CODE
    ): array {
        Log::info('JTExpress: Printing order', [
            'bill_code' => $billCode,
            'print_size' => $printSize,
            'print_code' => $printCode,
        ]);

        try {
            return $this->jtExpressClient->printOrder($billCode, $printSize, $printCode);

        } catch (\Throwable $e) {
            Log::error('JTExpress: Print order failed', [
                'bill_code' => $billCode,
                'error' => $e->getMessage(),
            ]);

            throw ShippingServiceException::printFailed(
                $billCode,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Convert an Order model to the array format expected by J&T Express
     *
     * @param Order $order Order model with loaded relationships
     * @return array Formatted order data
     * @throws InvalidShippingOrderException When required relationships are missing
     */
    private function convertOrderToArray(Order $order): array
    {
        // Validate required relationships are loaded
        $this->validateOrderRelationships($order);

        // Validate shipping address has required fields
        $this->validateShippingAddress($order);

        return [
            'id' => $order->id,
            'total' => $order->total ?? 0,
            'shippingAddress' => $this->formatShippingAddress($order),
            'orderItems' => $this->formatOrderItems($order),
        ];
    }

    /**
     * Validate that all required relationships are loaded
     *
     * @throws InvalidShippingOrderException
     */
    private function validateOrderRelationships(Order $order): void
    {
        $requiredRelations = ['shippingAddress', 'orderItems'];
        $missingRelations = [];

        foreach ($requiredRelations as $relation) {
            if (!$order->relationLoaded($relation)) {
                $missingRelations[] = $relation;
            }
        }

        if (!empty($missingRelations)) {
            throw InvalidShippingOrderException::missingRelationships($missingRelations);
        }

        // Validate orderItems is not empty
        if ($order->orderItems->isEmpty()) {
            throw InvalidShippingOrderException::emptyOrderItems();
        }
    }

    /**
     * Validate shipping address has minimum required fields
     *
     * @throws InvalidShippingOrderException
     */
    private function validateShippingAddress(Order $order): void
    {
        if (!$order->shippingAddress) {
            throw InvalidShippingOrderException::missingShippingAddress();
        }

        $address = $order->shippingAddress;
        $requiredFields = [
            'first_name' => 'First name',
            'phone' => 'Phone number',
            'address_line1' => 'Address',
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $label) {
            if (empty($address->$field)) {
                $missingFields[] = $label;
            }
        }

        if (!empty($missingFields)) {
            throw InvalidShippingOrderException::missingAddressFields($missingFields);
        }
    }

    /**
     * Format shipping address for J&T Express API
     */
    private function formatShippingAddress(Order $order): array
    {
        $address = $order->shippingAddress;

        return [
            'first_name' => $address->first_name,
            'last_name' => $address->last_name ?? '',
            'phone' => $address->phone,
            'address_line1' => $address->address_line1,
            'city' => [
                'name' => $address->city?->name ?? ''
            ],
            'state' => [
                'name' => $address->state?->name ?? ''
            ],
            'country' => [
                'code' => $address->country?->code ?? config('services.jt_express.default_country', self::DEFAULT_COUNTRY_CODE)
            ]
        ];
    }

    /**
     * Format order items for J&T Express API
     */
    private function formatOrderItems(Order $order): array
    {
        return $order->orderItems->map(function ($item) {
            // Warn if product relationship is not loaded
            if (!$item->relationLoaded('product')) {
                Log::warning('JTExpress: Product relationship not loaded for order item', [
                    'order_item_id' => $item->id,
                ]);
            }

            return [
                'product' => [
                    'name' => $item->product?->name ?? 'Product',
                    'description' => $item->product?->description ?? ''
                ],
                'quantity' => $item->quantity ?? 1,
                'price_at_purchase' => $item->price_at_purchase ?? 0
            ];
        })->toArray();
    }
}