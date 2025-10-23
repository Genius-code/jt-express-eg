<?php

namespace GeniusCode\JTExpressEg\Exceptions;

class InvalidOrderDataException extends JTExpressException
{
    public static function missingShippingAddress(): self
    {
        return new self('Shipping address is required for order creation');
    }

    public static function missingOrderItems(): self
    {
        return new self('Order items are required for order creation');
    }

    public static function invalidField(string $field, string $reason = ''): self
    {
        $message = "Invalid order data field: {$field}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        return new self($message);
    }
}