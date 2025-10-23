<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when shipping order data is invalid
 *
 * This exception is thrown when:
 * - Required order relationships are not loaded
 * - Shipping address is missing required fields
 * - Order items are empty
 * - Order data structure is invalid
 */
class InvalidShippingOrderException extends Exception
{
    /**
     * Create exception for missing relationships
     */
    public static function missingRelationships(array $relationships): self
    {
        return new self(
            'Order must have the following relationships loaded: ' . implode(', ', $relationships)
        );
    }

    /**
     * Create exception for empty order items
     */
    public static function emptyOrderItems(): self
    {
        return new self('Order must have at least one order item');
    }

    /**
     * Create exception for missing shipping address
     */
    public static function missingShippingAddress(): self
    {
        return new self('Order must have a shipping address');
    }

    /**
     * Create exception for missing address fields
     */
    public static function missingAddressFields(array $fields): self
    {
        return new self(
            'Shipping address is missing required fields: ' . implode(', ', $fields)
        );
    }
}