<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when shipping service operations fail
 *
 * This exception is thrown when:
 * - API communication fails
 * - Network errors occur
 * - External service errors happen
 * - Unexpected errors during shipping operations
 */
class ShippingServiceException extends Exception
{
    /**
     * Create exception for order creation failure
     */
    public static function orderCreationFailed(string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to create shipping order: {$reason}", 0, $previous);
    }

    /**
     * Create exception for order cancellation failure
     */
    public static function orderCancellationFailed(string $txlogisticId, string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to cancel order {$txlogisticId}: {$reason}", 0, $previous);
    }

    /**
     * Create exception for tracking failure
     */
    public static function trackingFailed(string $billCode, string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to track order {$billCode}: {$reason}", 0, $previous);
    }

    /**
     * Create exception for order retrieval failure
     */
    public static function orderRetrievalFailed(string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to retrieve orders: {$reason}", 0, $previous);
    }

    /**
     * Create exception for print failure
     */
    public static function printFailed(string $billCode, string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to print order {$billCode}: {$reason}", 0, $previous);
    }
}