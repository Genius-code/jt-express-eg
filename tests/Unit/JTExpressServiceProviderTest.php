<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\JTExpressService;
use GeniusCode\JTExpressEg\Tests\TestCase;

class JTExpressServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_jt_express_service_as_singleton(): void
    {
        $service1 = app(JTExpressService::class);
        $service2 = app(JTExpressService::class);

        $this->assertInstanceOf(JTExpressService::class, $service1);
        $this->assertSame($service1, $service2);
    }

    /** @test */
    public function it_registers_service_with_alias(): void
    {
        $service = app('jt-express');

        $this->assertInstanceOf(JTExpressService::class, $service);
    }

    /** @test */
    public function it_merges_config_from_package(): void
    {
        $this->assertNotNull(config('jt-express.apiAccount'));
        $this->assertNotNull(config('jt-express.privateKey'));
        $this->assertNotNull(config('jt-express.customerCode'));
        $this->assertNotNull(config('jt-express.customerPwd'));
    }

    /** @test */
    public function it_loads_sender_configuration(): void
    {
        $this->assertIsArray(config('jt-express.sender'));
        $this->assertArrayHasKey('name', config('jt-express.sender'));
        $this->assertArrayHasKey('mobile', config('jt-express.sender'));
        $this->assertArrayHasKey('city', config('jt-express.sender'));
    }
}
