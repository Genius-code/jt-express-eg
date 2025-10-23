<?php

namespace GeniusCode\JTExpressEg\Tests\Unit;

use GeniusCode\JTExpressEg\DTOs\AddressData;
use GeniusCode\JTExpressEg\Formatters\AddressFormatter;
use GeniusCode\JTExpressEg\Tests\TestCase;

class AddressFormatterTest extends TestCase
{
    protected AddressFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new AddressFormatter();
    }

    /** @test */
    public function it_formats_receiver_data_from_array(): void
    {
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

        $result = $this->formatter->formatReceiver($shippingAddress);

        $this->assertInstanceOf(AddressData::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('01234567890', $result->mobile);
        $this->assertEquals('Cairo', $result->city);
        $this->assertEquals('Test Street', $result->street);
        $this->assertEquals('10', $result->building);
        $this->assertEquals('5', $result->floor);
        $this->assertEquals('30.0444', $result->latitude);
        $this->assertEquals('31.2357', $result->longitude);
    }

    /** @test */
    public function it_formats_receiver_data_with_empty_address(): void
    {
        $result = $this->formatter->formatReceiver([]);

        $this->assertInstanceOf(AddressData::class, $result);
        $this->assertEquals('Test Receiver', $result->name);
        $this->assertEquals('01000000000', $result->mobile);
        $this->assertEquals('EGY', $result->countryCode);
    }

    /** @test */
    public function it_formats_receiver_data_from_object(): void
    {
        $shippingAddress = (object) [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '01987654321',
            'city' => (object) ['name' => 'Alexandria'],
            'state' => (object) ['name' => 'Alexandria Gov'],
            'street' => 'Corniche Street',
            'building' => '20',
            'user' => (object) ['email' => 'jane@example.com']
        ];

        $result = $this->formatter->formatReceiver($shippingAddress);

        $this->assertInstanceOf(AddressData::class, $result);
        $this->assertEquals('Jane Smith', $result->name);
        $this->assertEquals('01987654321', $result->mobile);
        $this->assertEquals('Alexandria', $result->city);
        $this->assertEquals('Corniche Street', $result->street);
        $this->assertEquals('jane@example.com', $result->mailBox);
    }

    /** @test */
    public function it_formats_sender_data_from_config(): void
    {
        config()->set('jt-express.sender.name', 'Company Name');
        config()->set('jt-express.sender.mobile', '01111111111');
        config()->set('jt-express.sender.city', 'Giza');

        $result = $this->formatter->formatSender();

        $this->assertInstanceOf(AddressData::class, $result);
        $this->assertEquals('Company Name', $result->name);
        $this->assertEquals('01111111111', $result->mobile);
        $this->assertEquals('Giza', $result->city);
        $this->assertEquals('EGY', $result->countryCode);
    }

    /** @test */
    public function it_returns_address_data_as_array(): void
    {
        $result = $this->formatter->formatReceiver([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '01234567890'
        ]);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('mobile', $array);
        $this->assertArrayHasKey('phone', $array);
        $this->assertArrayHasKey('countryCode', $array);
    }
}