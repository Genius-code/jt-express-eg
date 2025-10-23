<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\DTOs\OrderItemData;
use GeniusCode\JTExpressEg\Formatters\OrderItemFormatter;
use GeniusCode\JTExpressEg\Tests\TestCase;

class OrderItemFormatterTest extends TestCase
{
    protected OrderItemFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new OrderItemFormatter();
    }

    /** @test */
    public function it_formats_items_from_array(): void
    {
        $items = [
            [
                'product' => ['name' => 'Product 1', 'description' => 'Description 1'],
                'quantity' => 2,
                'price_at_purchase' => '50'
            ],
            [
                'product' => ['name' => 'Product 2', 'description' => 'Description 2'],
                'quantity' => 1,
                'price_at_purchase' => '100'
            ]
        ];

        $result = $this->formatter->format($items);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(OrderItemData::class, $result[0]);
        $this->assertEquals('Product 1', $result[0]->itemName);
        $this->assertEquals(2, $result[0]->number);
        $this->assertEquals('50', $result[0]->itemValue);
        $this->assertEquals('EGP', $result[0]->priceCurrency);
    }

    /** @test */
    public function it_formats_items_with_empty_array(): void
    {
        $result = $this->formatter->format([]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(OrderItemData::class, $result[0]);
        $this->assertEquals('Product', $result[0]->itemName);
        $this->assertEquals(1, $result[0]->number);
        $this->assertEquals('0', $result[0]->itemValue);
    }

    /** @test */
    public function it_formats_items_from_object(): void
    {
        $items = [
            (object) [
                'product' => (object) ['name' => 'Object Product', 'description' => 'Object Description'],
                'quantity' => 3,
                'price_at_purchase' => '75'
            ]
        ];

        $result = $this->formatter->format($items);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OrderItemData::class, $result[0]);
        $this->assertEquals('Object Product', $result[0]->itemName);
        $this->assertEquals(3, $result[0]->number);
        $this->assertEquals('75', $result[0]->itemValue);
    }

    /** @test */
    public function it_returns_order_item_data_as_array(): void
    {
        $items = [
            ['product' => ['name' => 'Test'], 'quantity' => 1, 'price_at_purchase' => '100']
        ];

        $result = $this->formatter->format($items);
        $array = $result[0]->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('itemName', $array);
        $this->assertArrayHasKey('number', $array);
        $this->assertArrayHasKey('itemValue', $array);
        $this->assertArrayHasKey('priceCurrency', $array);
    }
}