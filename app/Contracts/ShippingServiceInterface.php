<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\InvalidShippingOrderException;
use App\Exceptions\ShippingServiceException;
use App\Models\Order;

/**
 * Interface for shipping service implementations
 *
 * Defines the contract for shipping service providers (J&T Express, Aramex, etc.)
 * This allows for easy swapping of shipping providers and better testability.
 */
interface ShippingServiceInterface
{
    /**
     * Create a new shipping order
     *
     * @param array|Order $orderData Order model or array containing shipping details
     * @return array Response from shipping provider API
     * @throws InvalidShippingOrderException When order data is invalid
     * @throws ShippingServiceException When API communication fails
     */
    public function createOrder(array|Order $orderData): array;

    /**
     * Cancel an existing shipping order
     *
     * @param string $txlogisticId Transaction logistics ID from shipping provider
     * @param string $reason Cancellation reason
     * @return array Response from shipping provider API
     * @throws ShippingServiceException When cancellation fails
     */
    public function cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array;

    /**
     * Track a shipping order by tracking code
     *
     * @param string $billCode Waybill/tracking code
     * @return array Tracking information
     * @throws ShippingServiceException When tracking fails
     */
    public function trackOrder(string $billCode): array;

    /**
     * Get order details by serial number(s)
     *
     * @param string|array<string> $serialNumbers One or more serial numbers
     * @return array Order details from shipping provider
     * @throws ShippingServiceException When retrieval fails
     */
    public function getOrders(string|array $serialNumbers): array;

    /**
     * Generate printable shipping label
     *
     * @param string $billCode Waybill code
     * @param string $printSize Print size code
     * @param int $printCode Print format code
     * @return array Print data (usually base64 encoded PDF)
     * @throws ShippingServiceException When print generation fails
     */
    public function printOrder(string $billCode, string $printSize = '0', int $printCode = 0): array;
}