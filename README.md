# J&T Express Egypt Laravel SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-red.svg)](https://laravel.com)

A comprehensive Laravel package for seamless integration with J&T Express Egypt's shipping API. This SDK provides an elegant and developer-friendly interface to manage shipments, track orders, and handle logistics operations.

## Features

- **Order Management**: Create, cancel, and retrieve orders
- **Order Tracking**: Real-time shipment tracking with detailed status updates
- **Waybill Printing**: Generate and print shipping labels
- **Environment Support**: Separate production and demo/testing environments
- **Comprehensive Logging**: Built-in logging for debugging and monitoring
- **Secure Authentication**: Automatic signature generation and request authentication
- **Flexible Configuration**: Easily configurable via environment variables
- **Laravel Integration**: Service provider, facade support, and auto-discovery
- **Well-Tested**: Comprehensive test suite included

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Creating an Order](#creating-an-order)
  - [Canceling an Order](#canceling-an-order)
  - [Tracking an Order](#tracking-an-order)
  - [Getting Order Details](#getting-order-details)
  - [Printing Waybill](#printing-waybill)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Error Handling](#error-handling)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Guzzle HTTP Client 7.0 or higher

## Installation

Install the package via Composer:

```bash
composer require genius-code/jt-express-eg
```

### Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=jt-express-config
```

This will create a `config/jt-express.php` file in your application.

## Configuration

### Environment Variables

Add the following variables to your `.env` file:

```env
# J&T Express API Credentials
JT_API_ACCOUNT=your_api_account
JT_PRIVATE_KEY=your_private_key
JT_CUSTOMER_CODE=your_customer_code
JT_CUSTOMER_PWD=your_customer_password

# Sender Information (Your Company/Warehouse Details)
JT_SENDER_NAME="Your Company Name"
JT_SENDER_MOBILE=01000000000
JT_SENDER_PHONE=01000000000
JT_SENDER_PROV="الجيزة"
JT_SENDER_CITY="مدينة السادس من أكتوبر"
JT_SENDER_AREA="Your Area"
JT_SENDER_STREET="Your Street Address"
JT_SENDER_BUILDING="Building Number"
JT_SENDER_FLOOR="Floor Number"
JT_SENDER_FLATS="Apartment Number"
JT_SENDER_COMPANY="Your Company Name"
JT_SENDER_MAILBOX="company@example.com"
JT_SENDER_POSTCODE=""
JT_SENDER_LAT=""
JT_SENDER_LNG=""

# Optional: Digest for Print API (if required)
JT_DIGEST=
```

### Configuration File

The `config/jt-express.php` file structure:

```php
return [
    'apiAccount'   => env('JT_API_ACCOUNT'),
    'privateKey'   => env('JT_PRIVATE_KEY'),
    'customerCode' => env('JT_CUSTOMER_CODE'),
    'customerPwd'  => env('JT_CUSTOMER_PWD'),

    'sender' => [
        'name'      => env('JT_SENDER_NAME', 'Test Sender'),
        'mobile'    => env('JT_SENDER_MOBILE', '01000000000'),
        'phone'     => env('JT_SENDER_PHONE', '01000000000'),
        'prov'      => env('JT_SENDER_PROV', 'الجيزة'),
        'city'      => env('JT_SENDER_CITY', 'مدينة السادس من أكتوبر'),
        // ... other sender fields
    ],

    'digest' => env('JT_DIGEST', '')
];
```

### API Environments

The SDK automatically switches between environments based on your `APP_ENV` setting:

- **Production**: `APP_ENV=production` → `https://openapi.jtjms-eg.com`
- **Testing/Demo**: Any other value → `https://demoopenapi.jtjms-eg.com`

## Usage

### Using the Facade

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

// Use the facade throughout your application
$result = JTExpress::createOrder($orderData);
```

### Using Dependency Injection

```php
use Appleera1\JtExpressEg\JTExpressService;

class ShippingController extends Controller
{
    protected $jtExpress;

    public function __construct(JTExpressService $jtExpress)
    {
        $this->jtExpress = $jtExpress;
    }

    public function createShipment()
    {
        $result = $this->jtExpress->createOrder($orderData);
    }
}
```

### Creating an Order

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

$orderData = [
    'id' => 'YOUR_ORDER_ID', // Your internal order ID
    'deliveryType' => '04', // 01=Home, 02=Self-pickup, 04=Home delivery
    'payType' => 'PP_PM', // PP_PM=Prepaid, CC_CASH=Cash on delivery
    'expressType' => 'EZ', // EZ=Standard, KY=Express
    'weight' => 1.5, // Weight in kg
    'total' => '150.00', // Order total amount
    'totalQuantity' => '2', // Total items quantity
    'remark' => 'Handle with care',

    // Receiver/Shipping Address
    'shippingAddress' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Mohamed',
        'phone' => '01234567890',
        'city' => ['name' => 'القاهرة'],
        'state' => ['name' => 'القاهرة'],
        'street' => 'Street Name',
        'building' => '10',
        'floor' => '5',
        'flats' => '12',
        'area' => 'مدينة نصر',
        'latitude' => '30.0444',
        'longitude' => '31.2357'
    ],

    // Order Items
    'orderItems' => [
        [
            'product' => [
                'name' => 'T-Shirt',
                'description' => 'Cotton T-Shirt'
            ],
            'quantity' => 2,
            'price_at_purchase' => '75.00'
        ]
    ]
];

$result = JTExpress::createOrder($orderData);

if ($result['success']) {
    echo "Order created successfully!";
    echo "Waybill Code: " . $result['waybill_code'];
    echo "Tracking ID: " . $result['tx_logistic_id'];
    echo "Sorting Code: " . $result['sorting_code'];
} else {
    echo "Error: " . $result['error'];
}
```

### Canceling an Order

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

$txlogisticId = 'YOUR_ORDER_TX_LOGISTIC_ID';
$reason = 'Customer requested cancellation';

$result = JTExpress::cancelOrder($txlogisticId, $reason);

if ($result['success']) {
    echo "Order cancelled successfully!";
} else {
    echo "Error: " . $result['error'];
}
```

### Tracking an Order

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

$billCode = 'JTE123456789'; // The waybill code from order creation

$result = JTExpress::trackOrder($billCode);

if ($result['success']) {
    $trackingData = $result['data'];

    // Access tracking information
    foreach ($trackingData['data'][0]['traces'] ?? [] as $trace) {
        echo $trace['status'] . ' - ' . $trace['time'];
    }
} else {
    echo "Error: " . $result['error'];
}
```

### Getting Order Details

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

// Single order
$result = JTExpress::getOrders('ORDER_TX_LOGISTIC_ID');

// Multiple orders
$result = JTExpress::getOrders(['ORDER_ID_1', 'ORDER_ID_2', 'ORDER_ID_3']);

if ($result['success']) {
    $orders = $result['data']['data'];
    foreach ($orders as $order) {
        echo "Bill Code: " . $order['billCode'];
        echo "Status: " . $order['status'];
    }
} else {
    echo "Error: " . $result['error'];
}
```

### Printing Waybill

```php
use Appleera1\JtExpressEg\Facades\JTExpress;

$billCode = 'JTE123456789';
$printSize = '0'; // 0=A5, 1=100x100mm, 2=100x150mm
$printCode = 0; // 0=No barcode, 1=Include barcode

$result = JTExpress::printOrder($billCode, $printSize, $printCode);

if ($result['success']) {
    // The response typically contains a URL or base64 encoded PDF
    $waybillData = $result['data'];
    echo "Print URL: " . ($waybillData['url'] ?? 'Generated');
} else {
    echo "Error: " . $result['error'];

    // Handle specific error codes
    if ($result['error_code'] === '121003006') {
        echo "Order status does not support printing yet.";
    }
}
```

## API Reference

### `createOrder(array $orderData): array`

Creates a new shipping order with J&T Express.

**Parameters:**
- `$orderData` (array): Order details including shipping address and items

**Returns:**
- Array with keys: `success`, `data`, `status_code`, `waybill_code`, `tx_logistic_id`, `sorting_code`, `last_center_name`

**Common Order Data Fields:**
- `id`: Your internal order ID
- `deliveryType`: Delivery type code
- `payType`: Payment type (PP_PM, CC_CASH)
- `expressType`: Express type (EZ, KY)
- `weight`: Package weight in kg
- `total`: Order total amount
- `shippingAddress`: Receiver address details
- `orderItems`: Array of order items

---

### `cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array`

Cancels an existing order.

**Parameters:**
- `$txlogisticId` (string): The transaction logistic ID from order creation
- `$reason` (string): Reason for cancellation

**Returns:**
- Array with keys: `success`, `data`, `status_code`

---

### `trackOrder(string $billCode): array`

Tracks an order using the waybill code.

**Parameters:**
- `$billCode` (string): The waybill/bill code

**Returns:**
- Array with keys: `success`, `data`, `status_code`

---

### `getOrders(string|array $serialNumbers): array`

Retrieves order details by serial number(s).

**Parameters:**
- `$serialNumbers` (string|array): Single or multiple order serial numbers

**Returns:**
- Array with keys: `success`, `data`, `status_code`

---

### `printOrder(string $billCode, string $printSize = '0', int $printCode = 0): array`

Generates a printable waybill for an order.

**Parameters:**
- `$billCode` (string): The waybill code
- `$printSize` (string): Print size ('0'=A5, '1'=100x100mm, '2'=100x150mm)
- `$printCode` (int): Include barcode (0=No, 1=Yes)

**Returns:**
- Array with keys: `success`, `data`, `message`, `status_code`

## Testing

The package includes a comprehensive test suite using PHPUnit.

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/JTExpressServiceTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Test Structure

```
tests/
├── TestCase.php                          # Base test case
└── Unit/
    ├── JTExpressServiceTest.php          # Service tests
    ├── JTExpressServiceProviderTest.php  # Provider tests
    └── JTExpressFacadeTest.php           # Facade tests
```

## Error Handling

All methods return a standardized response array:

### Success Response

```php
[
    'success' => true,
    'data' => [...], // Response data from API
    'status_code' => 200,
    // Additional fields based on method
]
```

### Error Response

```php
[
    'success' => false,
    'error' => 'Error message',
    'code' => 'ERROR_CODE', // API error code
    'data' => [...], // Full response data
    'status_code' => 400
]
```

### Common Error Codes

- `145003050`: Illegal parameters
- `121003006`: Order status not printable
- `500`: Internal server error / Exception

### Exception Handling

```php
try {
    $result = JTExpress::createOrder($orderData);

    if (!$result['success']) {
        Log::error('J&T Order Creation Failed', [
            'error' => $result['error'],
            'code' => $result['code'] ?? null
        ]);
    }
} catch (\Exception $e) {
    Log::error('Exception during order creation', [
        'message' => $e->getMessage()
    ]);
}
```

## Security

### Best Practices

1. **Never commit credentials**: Keep your API keys in `.env` file and add it to `.gitignore`
2. **Use environment variables**: Always use `env()` helper for sensitive data
3. **Secure your endpoints**: Implement proper authentication for your API endpoints
4. **Validate input data**: Always validate and sanitize order data before sending
5. **Monitor logs**: Regularly check Laravel logs for suspicious activities

### Security Vulnerabilities

If you discover a security vulnerability, please email [moh.aly8890@gmail.com](mailto:moh.aly8890@gmail.com). All security vulnerabilities will be promptly addressed.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/genius-code/jt-express-eg.git
cd jt-express-eg

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit
```

### Coding Standards

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Keep backward compatibility when possible

## Changelog

### Version 1.0.0 (Current)

- Initial release
- Order creation and management
- Order tracking functionality
- Waybill printing
- Comprehensive test suite
- Laravel 10.x and 11.x support

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions:

- **Email**: [moh.aly8890@gmail.com](mailto:moh.aly8890@gmail.com)
- **Issues**: [GitHub Issues](https://github.com/genius-code/jt-express-eg/issues)

## Acknowledgments

- Built for Laravel developers integrating with J&T Express Egypt
- Inspired by the need for a clean, modern SDK for Egyptian logistics
- Special thanks to all contributors

## Related Resources

- [J&T Express Egypt Official Website](https://www.jtexpress.eg/)
- [Laravel Documentation](https://laravel.com/docs)
- [Guzzle HTTP Client](https://docs.guzzlephp.org/)

---

Made with ❤️ by [Genius Code](https://github.com/genius-code)