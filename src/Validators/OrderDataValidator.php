<?php

namespace Appleera1\JtExpressEg\Validators;

use Appleera1\JtExpressEg\Exceptions\InvalidOrderDataException;

class OrderDataValidator
{
    public function validate(array $orderData): void
    {
        $this->validateShippingAddress($orderData);
        $this->validateOrderItems($orderData);
    }

    public function validateShippingAddress(array $orderData): void
    {
        if (!isset($orderData['shippingAddress']) || empty($orderData['shippingAddress'])) {
            throw InvalidOrderDataException::missingShippingAddress();
        }
    }

    public function validateOrderItems(array $orderData): void
    {
        if (!isset($orderData['orderItems']) || empty($orderData['orderItems'])) {
            throw InvalidOrderDataException::missingOrderItems();
        }
    }

    public function validateOptional(array $orderData): void
    {
        // Add optional validations here
        // For example: weight ranges, dimensions, etc.
        if (isset($orderData['weight']) && $orderData['weight'] < 0) {
            throw InvalidOrderDataException::invalidField('weight', 'Weight must be positive');
        }

        if (isset($orderData['length']) && $orderData['length'] < 0) {
            throw InvalidOrderDataException::invalidField('length', 'Length must be positive');
        }

        if (isset($orderData['width']) && $orderData['width'] < 0) {
            throw InvalidOrderDataException::invalidField('width', 'Width must be positive');
        }

        if (isset($orderData['height']) && $orderData['height'] < 0) {
            throw InvalidOrderDataException::invalidField('height', 'Height must be positive');
        }
    }
}