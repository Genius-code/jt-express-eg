<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\Builders\OrderRequestBuilder;
use GeniusCode\JTExpressEg\DTOs\AddressData;
use GeniusCode\JTExpressEg\Tests\TestCase;
use Carbon\Carbon;

class OrderRequestBuilderTest extends TestCase
{

    private AddressData $receiver;
    private AddressData $sender;
    private array $items;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receiver = new AddressData(
            name: 'John Doe',
            mobile: '01000000000',
            phone: '01000000000',
            countryCode: 'EGY',
            prov: 'Cairo',
            city: 'Cairo',
            area: 'Nasr City',
            street: '123 Test Street'
        );
        $this->sender = new AddressData(
            name: 'Jane Doe',
            mobile: '01111111111',
            phone: '01111111111',
            countryCode: 'EGY',
            prov: 'Giza',
            city: '6th of October',
            area: '2nd District',
            street: '456 Sender Street'
        );
        $this->items = [['name' => 'Test Item']];
        Carbon::setTestNow(Carbon::create(2025, 1, 1, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    /** @test */
    public function it_builds_order_request_with_minimal_data()
    {
        $orderData = [
            'id' => 'ORDER123',
        ];

        $orderRequest = (new OrderRequestBuilder())->build('test_customer_code', 'test_digest', $orderData, $this->receiver, $this->sender, $this->items);

        $this->assertEquals('test_customer_code', $orderRequest->customerCode);
        $this->assertEquals('test_digest', $orderRequest->digest);
        $this->assertEquals('ORDER123', $orderRequest->txlogisticId);
        $this->assertSame($this->receiver, $orderRequest->receiver);
        $this->assertSame($this->sender, $orderRequest->sender);
        $this->assertSame($this->items, $orderRequest->items);
        $this->assertEquals('04', $orderRequest->deliveryType);
        $this->assertEquals('PP_PM', $orderRequest->payType);
        $this->assertEquals('EZ', $orderRequest->expressType);
        $this->assertEquals(1.0, $orderRequest->weight);
        $this->assertEquals('2025-01-01 12:00:00', $orderRequest->sendStartTime);
        $this->assertEquals('2025-01-02 12:00:00', $orderRequest->sendEndTime);
    }

    /** @test */
    public function it_builds_order_request_with_all_data()
    {
        $orderData = [
            'id' => 'ORDER123',
            'deliveryType' => '01',
            'payType' => 'CC_CASH',
            'expressType' => 'KY',
            'serviceType' => '02',
            'goodsType' => 'ITN2',
            'network' => 'TEST_NET',
            'length' => 10.5,
            'width' => 20.5,
            'height' => 30.5,
            'weight' => 2.5,
            'sendStartTime' => '2025-01-05 10:00:00',
            'sendEndTime' => '2025-01-06 10:00:00',
            'total' => '250.50',
            'remark' => 'Test remark',
            'invoiceNumber' => 'INV-123',
            'packingNumber' => 'PACK-123',
            'batchNumber' => 'BATCH-123',
            'billCode' => 'BILL-123',
            'operateType' => 2,
            'orderType' => '2',
            'expectDeliveryStartTime' => '2025-01-07 10:00:00',
            'expectDeliveryEndTime' => '2025-01-08 10:00:00',
            'totalQuantity' => '5',
            'offerFee' => '10.50',
        ];

        $orderRequest = (new OrderRequestBuilder())->build('test_customer_code', 'test_digest', $orderData, $this->receiver, $this->sender, $this->items);

        $this->assertEquals('ORDER123', $orderRequest->txlogisticId);
        $this->assertEquals('01', $orderRequest->deliveryType);
        $this->assertEquals('CC_CASH', $orderRequest->payType);
        $this->assertEquals('KY', $orderRequest->expressType);
        $this->assertEquals(2.5, $orderRequest->weight);
        $this->assertEquals('2025-01-05 10:00:00', $orderRequest->sendStartTime);
        $this->assertEquals('250.50', $orderRequest->itemsValue);
        $this->assertEquals('Test remark', $orderRequest->remark);
        $this->assertEquals('5', $orderRequest->totalQuantity);
    }

    /** @test */
    public function it_generates_txlogisticid_if_not_provided()
    {
        $orderData = [];
        $orderRequest = (new OrderRequestBuilder())->build('test_customer_code', 'test_digest', $orderData, $this->receiver, $this->sender, $this->items);
        $this->assertStringStartsWith('ORDER', $orderRequest->txlogisticId);
        $this->assertEquals(15, strlen($orderRequest->txlogisticId));
    }

    /** @test */
    public function it_uses_provided_id_as_txlogisticid()
    {
        $orderData = ['id' => 'MY-CUSTOM-ID'];
        $orderRequest = (new OrderRequestBuilder())->build('test_customer_code', 'test_digest', $orderData, $this->receiver, $this->sender, $this->items);
        $this->assertEquals('MY-CUSTOM-ID', $orderRequest->txlogisticId);
    }
}
