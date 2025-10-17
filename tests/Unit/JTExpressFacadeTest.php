<?php

namespace Appleera1\JtExpressEg\Tests\Unit;

use Appleera1\JtExpressEg\Facades\JTExpress;
use Appleera1\JtExpressEg\JTExpressService;
use Appleera1\JtExpressEg\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
    }

    /** @test */
    public function it_resolves_to_jt_express_service(): void
    {
        $resolved = JTExpress::getFacadeRoot();

        $this->assertInstanceOf(JTExpressService::class, $resolved);
    }

    /** @test */
    public function it_can_call_create_order_through_facade(): void
    {
        Http::fake([
            '*/api/order/addOrder' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => [
                    'billCode' => 'JTE123456789',
                    'txlogisticId' => 'ORDER0000000001',
                ]
            ], 200)
        ]);

        $result = JTExpress::createOrder(['id' => 'ORDER0000000001']);

        $this->assertTrue($result['success']);
        $this->assertEquals('JTE123456789', $result['waybill_code']);
    }

    /** @test */
    public function it_can_call_cancel_order_through_facade(): void
    {
        Http::fake([
            '*/api/order/cancelOrder' => Http::response([
                'code' => '1',
                'msg' => 'Success',
            ], 200)
        ]);

        $result = JTExpress::cancelOrder('ORDER0000000001', 'Test cancellation');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_can_call_track_order_through_facade(): void
    {
        Http::fake([
            '*/api/logistics/trace' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => []
            ], 200)
        ]);

        $result = JTExpress::trackOrder('JTE123456789');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_can_call_get_orders_through_facade(): void
    {
        Http::fake([
            '*/api/order/getOrders' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => []
            ], 200)
        ]);

        $result = JTExpress::getOrders('ORDER0000000001');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_can_call_print_order_through_facade(): void
    {
        Http::fake([
            '*/api/order/printOrder' => Http::response([
                'code' => '1',
                'msg' => 'Success',
                'data' => ['url' => 'https://example.com/waybill.pdf']
            ], 200)
        ]);

        $result = JTExpress::printOrder('JTE123456789');

        $this->assertTrue($result['success']);
    }
}
