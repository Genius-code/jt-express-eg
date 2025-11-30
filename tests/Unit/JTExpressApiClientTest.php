<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\Http\JTExpressApiClient;
use GeniusCode\JTExpressEg\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JTExpressApiClientTest extends TestCase
{
    private JTExpressApiClient $client;
    protected $baseUrl = 'https://example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new JTExpressApiClient($this->baseUrl);
        Http::fake();
        Log::shouldReceive('info');
    }

    /** @test */
    public function it_sends_create_order_request_correctly()
    {
        $bizContent = '{"key":"value"}';
        $headers = ['X-Test' => 'header'];

        $this->client->createOrder($bizContent, $headers);

        Http::assertSent(function ($request) use ($bizContent, $headers) {
            return $request->url() === $this->baseUrl . '/webopenplatformapi/api/order/addOrder' &&
                   $request->method() === 'POST' &&
                   $request['bizContent'] === $bizContent &&
                   $request->hasHeader('X-Test', 'header');
        });
    }

    /** @test */
    public function it_sends_cancel_order_request_correctly()
    {
        $bizContent = '{"key":"value"}';
        $headers = ['X-Test' => 'header'];

        $this->client->cancelOrder($bizContent, $headers);

        Http::assertSent(function ($request) use ($bizContent, $headers) {
            return $request->url() === $this->baseUrl . '/webopenplatformapi/api/order/cancelOrder' &&
                   $request->method() === 'POST' &&
                   $request['bizContent'] === $bizContent &&
                   $request->hasHeader('X-Test', 'header');
        });
    }

    /** @test */
    public function it_sends_track_order_request_correctly()
    {
        $bizContent = '{"key":"value"}';
        $headers = ['X-Test' => 'header'];

        $this->client->trackOrder($bizContent, $headers);

        Http::assertSent(function ($request) use ($bizContent, $headers) {
            return $request->url() === $this->baseUrl . '/webopenplatformapi/api/logistics/trace' &&
                   $request->method() === 'POST' &&
                   $request['bizContent'] === $bizContent &&
                   $request->hasHeader('X-Test', 'header');
        });
    }

    /** @test */
    public function it_sends_get_orders_request_correctly()
    {
        $bizContent = '{"key":"value"}';
        $headers = ['X-Test' => 'header'];

        $this->client->getOrders($bizContent, $headers);

        Http::assertSent(function ($request) use ($bizContent, $headers) {
            return $request->url() === $this->baseUrl . '/webopenplatformapi/api/order/getOrders' &&
                   $request->method() === 'POST' &&
                   $request['bizContent'] === $bizContent &&
                   $request->hasHeader('X-Test', 'header');
        });
    }

    /** @test */
    public function it_sends_print_order_request_correctly()
    {
        $bizContent = '{"key":"value"}';
        $headers = ['X-Test' => 'header'];

        $this->client->printOrder($bizContent, $headers);

        Http::assertSent(function ($request) use ($bizContent, $headers) {
            return $request->url() === $this->baseUrl . '/webopenplatformapi/api/order/printOrder' &&
                   $request->method() === 'POST' &&
                   $request['bizContent'] === $bizContent &&
                   $request->hasHeader('X-Test', 'header');
        });
    }
}
