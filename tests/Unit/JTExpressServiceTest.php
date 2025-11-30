<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\Builders\OrderRequestBuilder;
use GeniusCode\JTExpressEg\Exceptions\ApiException;
use GeniusCode\JTExpressEg\Exceptions\InvalidOrderDataException;
use GeniusCode\JTExpressEg\Formatters\AddressFormatter;
use GeniusCode\JTExpressEg\Formatters\OrderItemFormatter;
use GeniusCode\JTExpressEg\Handlers\OrderResponseHandler;
use GeniusCode\JTExpressEg\Http\JTExpressApiClient;
use GeniusCode\JTExpressEg\JTExpressService;
use GeniusCode\JTExpressEg\Tests\TestCase;
use GeniusCode\JTExpressEg\Validators\OrderDataValidator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

class JTExpressServiceTest extends TestCase
{
    protected JTExpressService $service;
    protected MockInterface|JTExpressApiClient $apiClientMock;
    protected MockInterface|OrderRequestBuilder $orderRequestBuilderMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->apiClientMock = $this->mock(JTExpressApiClient::class);
        $this->orderRequestBuilderMock = $this->mock(OrderRequestBuilder::class);
        $responseHandler = new OrderResponseHandler();
        $addressFormatter = new AddressFormatter();
        $itemFormatter = new OrderItemFormatter();
        $validator = new OrderDataValidator();

        // Create service instance with mocked dependencies
        $this->service = new JTExpressService(
            $this->apiClientMock,
            $responseHandler,
            $addressFormatter,
            $itemFormatter,
            $validator,
            $this->orderRequestBuilderMock, // Injected mock
            'test_api_account',
            'test_private_key',
            'test_customer_code',
            'test_customer_pwd'
        );

        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
    }

    private function mockHttpResponse(array $data, int $status): MockInterface|Response
    {
        $response = $this->mock(Response::class);
        $response->shouldReceive('json')->andReturn($data);
        $response->shouldReceive('successful')->andReturn($status >= 200 && $status < 300);
        $response->shouldReceive('status')->andReturn($status);
        return $response;
    }

    /** @test */
    public function it_can_create_order_successfully(): void
    {
        $response = $this->mockHttpResponse([
            'code' => '1',
            'msg' => 'Success',
            'data' => [
                'billCode' => 'JTE123456789',
                'txlogisticId' => 'ORDER0000000001',
                'sortingCode' => 'SC001',
                'lastCenterName' => 'Cairo Center'
            ]
        ], 200);

        $this->apiClientMock->shouldReceive('createOrder')->once()->andReturn($response);

        $this->orderRequestBuilderMock->shouldReceive('build')
            ->once()
            ->andReturn(new \GeniusCode\JTExpressEg\DTOs\OrderRequest( // Return a dummy OrderRequest DTO
                customerCode: 'test_customer_code',
                digest: 'test_digest',
                txlogisticId: 'ORDER0000000001',
                receiver: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'John Doe', mobile: '01000000000', phone: '01000000000', countryCode: 'EGY',
                    prov: 'Cairo', city: 'Cairo', area: 'Nasr City', street: '123 Test Street'
                ),
                sender: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'Jane Doe', mobile: '01111111111', phone: '01111111111', countryCode: 'EGY',
                    prov: 'Giza', city: '6th of October', area: '2nd District', street: '456 Sender Street'
                ),
                items: [new \GeniusCode\JTExpressEg\DTOs\OrderItemData(itemName: 'Test Item', number: 1, itemValue: '100')]
            ));

        $orderData = [
            'id' => 'ORDER0000000001',
            'shippingAddress' => [
                'first_name' => 'John', 'last_name' => 'Doe', 'phone' => '01234567890',
                'city' => ['name' => 'Cairo'], 'state' => ['name' => 'Cairo'], 'street' => 'Test Street',
            ],
            'orderItems' => [['product' => ['name' => 'Test Product'], 'quantity' => 1, 'price_at_purchase' => '100']]
        ];

        $result = $this->service->createOrder($orderData);

        $this->assertTrue($result['success']);
        $this->assertEquals('JTE123456789', $result['waybill_code']);
    }

    /** @test */
    public function it_can_update_order_successfully(): void
    {
        $response = $this->mockHttpResponse([
            'code' => '1',
            'msg' => 'Success',
            'data' => [
                'billCode' => 'JTE987654321',
                'txlogisticId' => 'ORDER0000000002',
                'sortingCode' => 'SC002',
                'lastCenterName' => 'Giza Center'
            ]
        ], 200);

        $this->apiClientMock->shouldReceive('createOrder')->once()->andReturn($response);

        // Mock the OrderRequestBuilder to check if build was called with operateType 2
        $this->orderRequestBuilderMock->shouldReceive('build')
            ->once()
            ->withArgs(function ($customerCode, $bizContentDigest, $orderData, $receiver, $sender, $items) {
                // Assert that operateType is 2
                return $orderData['operateType'] === 2;
            })
            ->andReturn(new \GeniusCode\JTExpressEg\DTOs\OrderRequest( // Return a dummy OrderRequest DTO
                customerCode: 'test_customer_code',
                digest: 'test_digest',
                txlogisticId: 'ORDER0000000002',
                receiver: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'John Doe', mobile: '01000000000', phone: '01000000000', countryCode: 'EGY',
                    prov: 'Cairo', city: 'Cairo', area: 'Nasr City', street: '123 Test Street'
                ),
                sender: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'Jane Doe', mobile: '01111111111', phone: '01111111111', countryCode: 'EGY',
                    prov: 'Giza', city: '6th of October', area: '2nd District', street: '456 Sender Street'
                ),
                items: [new \GeniusCode\JTExpressEg\DTOs\OrderItemData(itemName: 'Test Item', number: 1, itemValue: '100')]
            ));

        $orderData = [
            'id' => 'ORDER0000000002',
            'shippingAddress' => [
                'first_name' => 'Jane', 'last_name' => 'Smith', 'phone' => '01234567891',
                'city' => ['name' => 'Giza'], 'state' => ['name' => 'Giza'], 'street' => 'Update Street',
            ],
            'orderItems' => [['product' => ['name' => 'Updated Product'], 'quantity' => 2, 'price_at_purchase' => '200']]
        ];

        $result = $this->service->updateOrder($orderData);

        $this->assertTrue($result['success']);
        $this->assertEquals('JTE987654321', $result['waybill_code']);
    }

    /** @test */
    public function it_throws_api_exception_on_create_order_failure(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid parameters');
        $this->expectExceptionCode(0);

        $this->orderRequestBuilderMock->shouldReceive('build')
            ->once()
            ->andReturn(new \GeniusCode\JTExpressEg\DTOs\OrderRequest(
                customerCode: 'test_customer_code',
                digest: 'test_digest',
                txlogisticId: 'ORDER0000000001',
                receiver: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'John Doe', mobile: '01000000000', phone: '01000000000', countryCode: 'EGY',
                    prov: 'Cairo', city: 'Cairo', area: 'Nasr City', street: '123 Test Street'
                ),
                sender: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'Jane Doe', mobile: '01111111111', phone: '01111111111', countryCode: 'EGY',
                    prov: 'Giza', city: '6th of October', area: '2nd District', street: '456 Sender Street'
                ),
                items: [new \GeniusCode\JTExpressEg\DTOs\OrderItemData(itemName: 'Test Item', number: 1, itemValue: '100')]
            ));

        $response = $this->mockHttpResponse(['code' => '0', 'msg' => 'Invalid parameters'], 400);
        $this->apiClientMock->shouldReceive('createOrder')->once()->andReturn($response);

        $this->service->createOrder([
            'id' => 'ORDER0000000001',
            'shippingAddress' => ['first_name' => 'John', 'phone' => '01234567890'],
            'orderItems' => [['product' => ['name' => 'Test'], 'quantity' => 1]]
        ]);
    }

    /** @test */
    public function it_throws_api_exception_on_http_exception(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('An unexpected error occurred: Connection timeout');

        $this->orderRequestBuilderMock->shouldReceive('build')
            ->once()
            ->andReturn(new \GeniusCode\JTExpressEg\DTOs\OrderRequest(
                customerCode: 'test_customer_code',
                digest: 'test_digest',
                txlogisticId: 'ORDER0000000001',
                receiver: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'John Doe', mobile: '01000000000', phone: '01000000000', countryCode: 'EGY',
                    prov: 'Cairo', city: 'Cairo', area: 'Nasr City', street: '123 Test Street'
                ),
                sender: new \GeniusCode\JTExpressEg\DTOs\AddressData(
                    name: 'Jane Doe', mobile: '01111111111', phone: '01111111111', countryCode: 'EGY',
                    prov: 'Giza', city: '6th of October', area: '2nd District', street: '456 Sender Street'
                ),
                items: [new \GeniusCode\JTExpressEg\DTOs\OrderItemData(itemName: 'Test Item', number: 1, itemValue: '100')]
            ));

        $this->apiClientMock->shouldReceive('createOrder')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $this->service->createOrder([
            'id' => 'ORDER0000000001',
            'shippingAddress' => ['first_name' => 'John', 'phone' => '01234567890'],
            'orderItems' => [['product' => ['name' => 'Test'], 'quantity' => 1]]
        ]);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_order_data(): void
    {
        $this->expectException(InvalidOrderDataException::class);
        $this->expectExceptionMessage('Shipping address is required for order creation');

        $this->service->createOrder(['id' => 'ORDER0000000001', 'orderItems' => []]);
    }

    /** @test */
    public function it_can_cancel_order_successfully(): void
    {
        $response = $this->mockHttpResponse(['code' => '1', 'msg' => 'Order cancelled successfully'], 200);
        $this->apiClientMock->shouldReceive('cancelOrder')->once()->andReturn($response);

        $result = $this->service->cancelOrder('ORDER0000000001', 'Customer request');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_throws_api_exception_on_cancel_order_failure(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Order not found');

        $response = $this->mockHttpResponse(['code' => '0', 'msg' => 'Order not found'], 404);
        $this->apiClientMock->shouldReceive('cancelOrder')->once()->andReturn($response);

        $this->service->cancelOrder('INVALID_ORDER');
    }

    /** @test */
    public function it_can_track_order_successfully(): void
    {
        $response = $this->mockHttpResponse([
            'code' => '1',
            'msg' => 'Success',
            'data' => [['billCode' => 'JTE123456789', 'traces' => []]]
        ], 200);
        $this->apiClientMock->shouldReceive('trackOrder')->once()->andReturn($response);

        $result = $this->service->trackOrder('JTE123456789');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    /** @test */
    public function it_throws_api_exception_on_track_order_failure(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Bill code not found');

        $response = $this->mockHttpResponse(['msg' => 'Bill code not found'], 404);
        $this->apiClientMock->shouldReceive('trackOrder')->once()->andReturn($response);

        $this->service->trackOrder('INVALID_CODE');
    }

    /** @test */
    public function it_can_get_orders_successfully(): void
    {
        $response = $this->mockHttpResponse(['code' => '1', 'msg' => 'Success', 'data' => []], 200);
        $this->apiClientMock->shouldReceive('getOrders')->once()->andReturn($response);

        $result = $this->service->getOrders('ORDER0000000001');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_can_print_order_successfully(): void
    {
        $response = $this->mockHttpResponse(['code' => '1', 'msg' => 'Print successful', 'data' => []], 200);
        $this->apiClientMock->shouldReceive('printOrder')->once()->andReturn($response);

        $result = $this->service->printOrder('JTE123456789');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_throws_api_exception_on_print_order_failure(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Illegal parameters');

        $response = $this->mockHttpResponse(['code' => '145003050', 'msg' => 'Illegal parameters'], 400);
        $this->apiClientMock->shouldReceive('printOrder')->once()->andReturn($response);

        $this->service->printOrder('INVALID_CODE');
    }
}

