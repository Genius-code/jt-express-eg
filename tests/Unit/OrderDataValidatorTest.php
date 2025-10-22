<?php

namespace Appleera1\JtExpressEg\Tests\Unit;

use Appleera1\JtExpressEg\Exceptions\InvalidOrderDataException;
use Appleera1\JtExpressEg\Validators\OrderDataValidator;
use Appleera1\JtExpressEg\Tests\TestCase;

class OrderDataValidatorTest extends TestCase
{
    protected OrderDataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new OrderDataValidator();
    }

    /** @test */
    public function it_throws_exception_when_shipping_address_is_missing(): void
    {
        $this->expectException(InvalidOrderDataException::class);
        $this->expectExceptionMessage('Shipping address is required');

        $this->validator->validate([
            'orderItems' => [['product' => ['name' => 'Test']]]
        ]);
    }

    /** @test */
    public function it_throws_exception_when_order_items_are_missing(): void
    {
        $this->expectException(InvalidOrderDataException::class);
        $this->expectExceptionMessage('Order items are required');

        $this->validator->validate([
            'shippingAddress' => ['name' => 'Test']
        ]);
    }

    /** @test */
    public function it_passes_validation_with_valid_data(): void
    {
        $validData = [
            'shippingAddress' => ['name' => 'Test', 'phone' => '01234567890'],
            'orderItems' => [['product' => ['name' => 'Product'], 'quantity' => 1]]
        ];

        // Should not throw exception
        $this->validator->validate($validData);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_for_negative_weight(): void
    {
        $this->expectException(InvalidOrderDataException::class);
        $this->expectExceptionMessage('Weight must be positive');

        $this->validator->validateOptional([
            'weight' => -5
        ]);
    }

    /** @test */
    public function it_throws_exception_for_negative_dimensions(): void
    {
        $this->expectException(InvalidOrderDataException::class);

        $this->validator->validateOptional([
            'length' => -10
        ]);
    }

    /** @test */
    public function it_passes_optional_validation_with_valid_values(): void
    {
        $this->validator->validateOptional([
            'weight' => 5,
            'length' => 10,
            'width' => 20,
            'height' => 15
        ]);

        $this->assertTrue(true);
    }
}