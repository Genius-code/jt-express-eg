<?php

namespace Appleera1\JtExpressEg\Tests\Unit;

use Appleera1\JtExpressEg\JTExpressService;
use Appleera1\JtExpressEg\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressServiceTest extends TestCase
{
    protected JTExpressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JTExpressService();
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
    }

    /** @test */
    public function it_can_calculate_biz_content_digest(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateBizContentDigest');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'customerCode', 'customerPwd', 'privateKey');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(base64_encode(md5('customerCodecustomerPwdprivateKey', true)), $result);
    }

    /** @test */
    public function it_can_calculate_header_digest(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateHeaderDigest');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'bizContent', 'privateKey');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(base64_encode(md5('bizContentprivateKey', true)), $result);
    }

    /** @test */
    public function it_can_generate_proper_headers(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getHeaders');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'test-digest', '1234567890');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Accept', $result);
        $this->assertArrayHasKey('Content-Type', $result);
        $this->assertArrayHasKey('apiAccount', $result);
        $this->assertArrayHasKey('digest', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('application/json', $result['Accept']);
        $this->assertEquals('application/x-www-form-urlencoded', $result['Content-Type']);
        $this->assertEquals('test-digest', $result['digest']);
        $this->assertEquals('1234567890', $result['timestamp']);
    }

    /** @test */
    public function it_can_create_order_successfully(): void
    {
        Http::fake([
            '*/api/order/addOrder' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => [
                    'billCode' => 'JTE123456789',
                    'txlogisticId' => 'ORDER0000000001',
                    'sortingCode' => 'SC001',
                    'lastCenterName' => 'Cairo Center'
                ]
            ], 200)
        ]);

        $orderData = [
            'id' => 'ORDER0000000001',
            'deliveryType' => '04',
            'payType' => 'PP_PM',
            'expressType' => 'EZ',
            'weight' => 1.5,
            'total' => '100',
            'shippingAddress' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '01234567890',
                'city' => ['name' => 'Cairo'],
                'state' => ['name' => 'Cairo'],
                'street' => 'Test Street',
            ],
            'orderItems' => []
        ];

        $result = $this->service->createOrder($orderData);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('JTE123456789', $result['waybill_code']);
        $this->assertEquals('ORDER0000000001', $result['tx_logistic_id']);
        $this->assertEquals('SC001', $result['sorting_code']);
        $this->assertEquals('Cairo Center', $result['last_center_name']);
    }

    /** @test */
    public function it_handles_create_order_failure(): void
    {
        Http::fake([
            '*/api/order/addOrder' => Http::response([
                'code' => '0',
                'msg' => 'Invalid parameters',
            ], 400)
        ]);

        $orderData = ['id' => 'ORDER0000000001'];
        $result = $this->service->createOrder($orderData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid parameters', $result['error']);
        $this->assertEquals(400, $result['status_code']);
    }

    /** @test */
    public function it_handles_create_order_exception(): void
    {
        Http::fake([
            '*/api/order/addOrder' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $result = $this->service->createOrder(['id' => 'ORDER0000000001']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
        $this->assertEquals(500, $result['status_code']);
    }

    /** @test */
    public function it_can_cancel_order_successfully(): void
    {
        Http::fake([
            '*/api/order/cancelOrder' => Http::response([
                'code' => '1',
                'msg' => 'Order cancelled successfully',
            ], 200)
        ]);

        $result = $this->service->cancelOrder('ORDER0000000001', 'Customer request');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    /** @test */
    public function it_handles_cancel_order_failure(): void
    {
        Http::fake([
            '*/api/order/cancelOrder' => Http::response([
                'code' => '0',
                'msg' => 'Order not found',
            ], 404)
        ]);

        $result = $this->service->cancelOrder('INVALID_ORDER');

        $this->assertFalse($result['success']);
        $this->assertEquals('Order not found', $result['error']);
        $this->assertEquals(404, $result['status_code']);
    }

    /** @test */
    public function it_can_track_order_successfully(): void
    {
        Http::fake([
            '*/api/logistics/trace' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => [
                    [
                        'billCode' => 'JTE123456789',
                        'traces' => [
                            ['status' => 'picked_up', 'time' => '2025-01-15 10:00:00'],
                            ['status' => 'in_transit', 'time' => '2025-01-15 12:00:00'],
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->trackOrder('JTE123456789');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertArrayHasKey('data', $result);
    }

    /** @test */
    public function it_handles_track_order_failure(): void
    {
        Http::fake([
            '*/api/logistics/trace' => Http::response([
                'code' => '0',
                'msg' => 'Bill code not found',
            ], 404)
        ]);

        $result = $this->service->trackOrder('INVALID_CODE');

        $this->assertFalse($result['success']);
        $this->assertEquals('Bill code not found', $result['error']);
        $this->assertEquals(404, $result['status_code']);
    }

    /** @test */
    public function it_can_get_orders_successfully(): void
    {
        Http::fake([
            '*/api/order/getOrders' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => [
                    [
                        'txlogisticId' => 'ORDER0000000001',
                        'billCode' => 'JTE123456789',
                        'status' => 'delivered'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->getOrders('ORDER0000000001');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    /** @test */
    public function it_can_get_orders_with_array_of_serial_numbers(): void
    {
        Http::fake([
            '*/api/order/getOrders' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => []
            ], 200)
        ]);

        $result = $this->service->getOrders(['ORDER0000000001', 'ORDER0000000002']);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    /** @test */
    public function it_can_print_order_successfully(): void
    {
        Http::fake([
            '*/api/order/printOrder' => Http::response([
                'code' => '1',
                'msg' => 'Print successful',
                'data' => ['url' => 'https://example.com/waybill.pdf']
            ], 200)
        ]);

        $result = $this->service->printOrder('JTE123456789');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('Print successful', $result['message']);
    }

    /** @test */
    public function it_handles_print_order_illegal_parameters(): void
    {
        Http::fake([
            '*/api/order/printOrder' => Http::response([
                'code' => '145003050',
                'msg' => 'Illegal parameters',
            ], 400)
        ]);

        $result = $this->service->printOrder('INVALID_CODE');

        $this->assertFalse($result['success']);
        $this->assertEquals('Illegal parameters', $result['error']);
        $this->assertEquals('145003050', $result['error_code']);
    }

    /** @test */
    public function it_handles_print_order_not_printable_status(): void
    {
        Http::fake([
            '*/api/order/printOrder' => Http::response([
                'code' => '121003006',
                'msg' => 'Order status not printable',
            ], 400)
        ]);

        $result = $this->service->printOrder('JTE123456789');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not support printing', $result['error']);
        $this->assertEquals('121003006', $result['error_code']);
    }

    /** @test */
    public function it_formats_receiver_data_from_array(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatReceiverData');
        $method->setAccessible(true);

        $shippingAddress = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '01234567890',
            'city' => ['name' => 'Cairo'],
            'state' => ['name' => 'Cairo Governorate'],
            'street' => 'Test Street',
            'building' => '10',
            'floor' => '5',
            'latitude' => '30.0444',
            'longitude' => '31.2357'
        ];

        $result = $method->invoke($this->service, $shippingAddress);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('01234567890', $result['mobile']);
        $this->assertEquals('Cairo', $result['city']);
        $this->assertEquals('Test Street', $result['street']);
        $this->assertEquals('10', $result['building']);
        $this->assertEquals('5', $result['floor']);
        $this->assertEquals('30.0444', $result['latitude']);
        $this->assertEquals('31.2357', $result['longitude']);
    }

    /** @test */
    public function it_formats_receiver_data_with_empty_address(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatReceiverData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, []);

        $this->assertIsArray($result);
        $this->assertEquals('Test Receiver', $result['name']);
        $this->assertEquals('01000000000', $result['mobile']);
        $this->assertEquals('EGY', $result['countryCode']);
    }

    /** @test */
    public function it_formats_sender_data_from_config(): void
    {
        config()->set('jt-express.sender.name', 'Company Name');
        config()->set('jt-express.sender.mobile', '01111111111');
        config()->set('jt-express.sender.city', 'Giza');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatSenderData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service);

        $this->assertIsArray($result);
        $this->assertEquals('Company Name', $result['name']);
        $this->assertEquals('01111111111', $result['mobile']);
        $this->assertEquals('Giza', $result['city']);
        $this->assertEquals('EGY', $result['countryCode']);
    }

    /** @test */
    public function it_formats_items_from_array(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatItems');
        $method->setAccessible(true);

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

        $result = $method->invoke($this->service, $items);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Product 1', $result[0]['itemName']);
        $this->assertEquals(2, $result[0]['number']);
        $this->assertEquals('50', $result[0]['itemValue']);
        $this->assertEquals('EGP', $result[0]['priceCurrency']);
    }

    /** @test */
    public function it_formats_items_with_empty_array(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatItems');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, []);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Product', $result[0]['itemName']);
        $this->assertEquals(1, $result[0]['number']);
    }

    /** @test */
    public function it_uses_production_base_url_in_production_env(): void
    {
        config()->set('app.env', 'production');
        $service = new JTExpressService();

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);

        $this->assertEquals('https://openapi.jtjms-eg.com', $property->getValue($service));
    }

    /** @test */
    public function it_uses_demo_base_url_in_non_production_env(): void
    {
        config()->set('app.env', 'testing');
        $service = new JTExpressService();

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);

        $this->assertEquals('https://demoopenapi.jtjms-eg.com', $property->getValue($service));
    }
}
