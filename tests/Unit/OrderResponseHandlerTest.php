<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\Exceptions\ApiException;
use GeniusCode\JTExpressEg\Handlers\OrderResponseHandler;
use GeniusCode\JTExpressEg\Tests\TestCase;
use Illuminate\Http\Client\Response;
use Mockery;

class OrderResponseHandlerTest extends TestCase
{
    private OrderResponseHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new OrderResponseHandler();
    }

    private function createMockResponse(array $data, int $status, bool $successful = true): Response
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')->andReturn($data);
        $response->shouldReceive('status')->andReturn($status);
        $response->shouldReceive('successful')->andReturn($successful);
        return $response;
    }

    /** @test */
    public function handle_with_exception_returns_success_payload_for_successful_response()
    {
        $responseData = [
            'code' => '1',
            'msg' => 'Success',
            'data' => [
                'billCode' => 'JTE123',
                'txlogisticId' => 'ORDER123',
                'sortingCode' => 'SC1',
                'lastCenterName' => 'Cairo',
            ],
        ];
        $response = $this->createMockResponse($responseData, 200);

        $result = $this->handler->handleWithException($response);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('JTE123', $result['waybill_code']);
        $this->assertEquals('ORDER123', $result['tx_logistic_id']);
        $this->assertEquals('SC1', $result['sorting_code']);
        $this->assertEquals('Cairo', $result['last_center_name']);
        $this->assertEquals($responseData, $result['data']);
    }

    /** @test */
    public function handle_with_exception_throws_api_exception_for_failed_api_response()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Order not found');

        $responseData = ['code' => '101', 'msg' => 'Order not found'];
        $response = $this->createMockResponse($responseData, 200);

        $this->handler->handleWithException($response);
    }

    /** @test */
    public function handle_with_exception_throws_api_exception_for_http_error_response()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Internal Server Error');

        $responseData = ['msg' => 'Internal Server Error'];
        $response = $this->createMockResponse($responseData, 500, false);

        $this->handler->handleWithException($response);
    }

    /** @test */
    public function handle_returns_success_array_for_successful_response()
    {
        $responseData = ['code' => '1', 'msg' => 'Success', 'data' => []];
        $response = $this->createMockResponse($responseData, 200);

        $result = $this->handler->handle($response);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    /** @test */
    public function handle_returns_error_array_for_failed_api_response()
    {
        $responseData = ['code' => '101', 'msg' => 'Order not found'];
        $response = $this->createMockResponse($responseData, 200);

        $result = $this->handler->handle($response);

        $this->assertFalse($result['success']);
        $this->assertEquals('Order not found', $result['error']);
        $this->assertEquals('101', $result['code']);
    }

    /** @test */
    public function handle_returns_error_array_for_http_error_response()
    {
        $responseData = ['msg' => 'Internal Server Error'];
        $response = $this->createMockResponse($responseData, 500, false);

        $result = $this->handler->handle($response);

        $this->assertFalse($result['success']);
        $this->assertEquals('Internal Server Error', $result['error']);
    }
}
