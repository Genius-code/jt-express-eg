# J&T Express Egypt Laravel SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-red.svg)](https://laravel.com)
[![Tests](https://img.shields.io/badge/tests-43%20passing-brightgreen.svg)](https://github.com/genius-code/jt-express-eg)

A **modern, type-safe, and production-ready** Laravel package for seamless integration with J&T Express Egypt's shipping API. Built with best practices, comprehensive testing, and developer experience in mind.

## ✨ Features

### Core Functionality
- **Order Management**: Create, cancel, and retrieve orders with ease
- **Real-time Tracking**: Track shipments with detailed status updates
- **Waybill Printing**: Generate and print shipping labels
- **Multi-Environment**: Separate production and demo/testing environments

### Developer Experience
- ✅ **Type-Safe**: Full PHP 8.1+ type declarations and immutable DTOs
- ✅ **Validated Input**: Automatic validation before API calls
- ✅ **Better Error Handling**: Specific exception types with rich context
- ✅ **Well-Tested**: 43 tests with 139 assertions (100% passing)
- ✅ **Clean Architecture**: SOLID principles and separation of concerns
- ✅ **Comprehensive Logging**: Built-in logging for debugging
- ✅ **Laravel Integration**: Service provider, facade, and auto-discovery

### Security & Reliability
- 🔒 **Secure Authentication**: Automatic signature generation
- 🛡️ **Input Validation**: Pre-request validation prevents bad requests
- 📝 **Audit Trail**: Comprehensive logging for compliance
- ⚡ **Performance**: Optimized for production use

## 📋 Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Usage Examples](#usage-examples)
- [Advanced Features](#advanced-features)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Error Handling](#error-handling)
- [Architecture](#architecture)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)

## 🔧 Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 10.x or 11.x
- **Dependencies**: Guzzle HTTP Client 7.0+

## 📦 Installation

Install via Composer:

```bash
composer require genius-code/jt-express-eg:^1.0
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=jt-express-config
```

This creates `config/jt-express.php` for customization.

## ⚙️ Configuration

### Environment Variables

Add to your `.env` file:

```env
# J&T Express API Credentials
JT_API_ACCOUNT=your_api_account
JT_PRIVATE_KEY=your_private_key
JT_CUSTOMER_CODE=your_customer_code
JT_CUSTOMER_PWD=your_customer_password

# Sender Information (Your Company/Warehouse)
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
```

### Environment Selection

The SDK automatically switches between environments:

- **Production**: `APP_ENV=production` → `https://openapi.jtjms-eg.com`
- **Testing/Demo**: Other values → `https://demoopenapi.jtjms-eg.com`

## 🚀 Quick Start

### Using the Facade (Recommended)

```php
use GeniusCode\JTExpressEg\Facades\JTExpress;

$result = JTExpress::createOrder([
    'shippingAddress' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Mohamed',
        'phone' => '01234567890',
        'city' => ['name' => 'القاهرة'],
        'street' => 'Street Name'
    ],
    'orderItems' => [
        [
            'product' => ['name' => 'T-Shirt'],
            'quantity' => 2,
            'price_at_purchase' => '75.00'
        ]
    ]
]);

if ($result['success']) {
    echo "Waybill: " . $result['waybill_code'];
}
```

### Using Dependency Injection

```php
use GeniusCode\JTExpressEg\JTExpressService;

class ShippingController extends Controller
{
    public function __construct(
        private JTExpressService $jtExpress
    ) {}

    public function ship()
    {
        $result = $this->jtExpress->createOrder($orderData);
    }
}
```

## 📚 Usage Examples

### Creating an Order

```php
use GeniusCode\JTExpressEg\Facades\JTExpress;

$orderData = [
    'id' => 'ORDER-12345', // Your internal order ID (optional)

    // Optional: Delivery preferences
    'deliveryType' => '04', // 01=Home, 02=Pickup, 04=Delivery
    'payType' => 'PP_PM',   // PP_PM=Prepaid, CC_CASH=COD
    'expressType' => 'EZ',  // EZ=Standard, KY=Express
    'weight' => 1.5,        // Weight in kg
    'total' => '150.00',    // Order total

    // Required: Shipping Address
    'shippingAddress' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Mohamed',
        'phone' => '01234567890',
        'city' => ['name' => 'القاهرة'],
        'state' => ['name' => 'القاهرة'],
        'street' => 'Street Name',
        'building' => '10',
        'floor' => '5',
        'area' => 'مدينة نصر',

        // Optional address fields
        'flats' => '12',
        'latitude' => '30.0444',
        'longitude' => '31.2357'
    ],

    // Required: Order Items
    'orderItems' => [
        [
            'product' => [
                'name' => 'T-Shirt',
                'description' => 'Cotton T-Shirt'
            ],
            'quantity' => 2,
            'price_at_purchase' => '75.00'
        ],
        [
            'product' => [
                'name' => 'Jeans',
                'description' => 'Blue Jeans'
            ],
            'quantity' => 1,
            'price_at_purchase' => '150.00'
        ]
    ],

    // Optional: Additional details
    'remark' => 'Handle with care',
    'totalQuantity' => '3',
];

$result = JTExpress::createOrder($orderData);

if ($result['success']) {
    // Order created successfully
    $waybillCode = $result['waybill_code'];        // JTE123456789
    $trackingId = $result['tx_logistic_id'];       // ORDER-12345
    $sortingCode = $result['sorting_code'];        // SC001
    $centerName = $result['last_center_name'];     // Cairo Hub

    // Store these for tracking later
    DB::table('shipments')->insert([
        'order_id' => $orderData['id'],
        'waybill_code' => $waybillCode,
        'tracking_id' => $trackingId,
    ]);
} else {
    // Handle error
    Log::error('Order creation failed', [
        'error' => $result['error'],
        'code' => $result['code'] ?? null,
        'status' => $result['status_code']
    ]);
}
```

### Canceling an Order

```php
$txlogisticId = 'ORDER-12345'; // From order creation
$reason = 'Customer requested cancellation';

$result = JTExpress::cancelOrder($txlogisticId, $reason);

if ($result['success']) {
    echo "Order cancelled successfully";
} else {
    echo "Cancellation failed: " . $result['error'];
}
```

### Tracking an Order

```php
$billCode = 'JTE123456789'; // Waybill code from order creation

$result = JTExpress::trackOrder($billCode);

if ($result['success']) {
    foreach ($result['data']['data'][0]['traces'] ?? [] as $trace) {
        echo "{$trace['status']} - {$trace['time']}<br>";
        echo "{$trace['desc']}<br>";
    }
}
```

### Getting Order Details

```php
// Single order
$result = JTExpress::getOrders('ORDER-12345');

// Multiple orders
$result = JTExpress::getOrders([
    'ORDER-12345',
    'ORDER-12346',
    'ORDER-12347'
]);

if ($result['success']) {
    foreach ($result['data']['data'] as $order) {
        echo "Bill Code: {$order['billCode']}<br>";
        echo "Status: {$order['status']}<br>";
        echo "Weight: {$order['weight']} kg<br>";
    }
}
```

### Printing Waybill

```php
$billCode = 'JTE123456789';
$printSize = '0'; // 0=A5, 1=100x100mm, 2=100x150mm
$printCode = 0;   // 0=No barcode, 1=Include barcode

$result = JTExpress::printOrder($billCode, $printSize, $printCode);

if ($result['success']) {
    $pdfUrl = $result['data']['url'] ?? null;

    // Download or display the waybill
    if ($pdfUrl) {
        return redirect($pdfUrl);
    }
} else {
    // Handle specific errors
    if ($result['error_code'] === '121003006') {
        echo "Order not ready for printing yet";
    } else {
        echo "Print error: " . $result['error'];
    }
}
```

## 🎯 Advanced Features

### Validation

The package automatically validates order data before making API calls:

```php
// This will fail validation (missing required fields)
$result = JTExpress::createOrder([
    'id' => 'ORDER-123'
    // Missing shippingAddress and orderItems
]);

// Returns:
[
    'success' => false,
    'error' => 'Shipping address is required for order creation',
    'status_code' => 400
]
```

### Using Individual Components

For advanced use cases, you can use formatters and validators directly:

```php
use GeniusCode\JTExpressEg\Formatters\AddressFormatter;
use GeniusCode\JTExpressEg\Validators\OrderDataValidator;

// Format address
$formatter = new AddressFormatter();
$addressDTO = $formatter->formatReceiver($shippingData);

// Validate order
$validator = new OrderDataValidator();
try {
    $validator->validate($orderData);
} catch (InvalidOrderDataException $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

### Exception Handling

The package provides specific exception types:

```php
use GeniusCode\JTExpressEg\Exceptions\InvalidOrderDataException;
use GeniusCode\JTExpressEg\Exceptions\ApiException;

try {
    $result = JTExpress::createOrder($orderData);
} catch (InvalidOrderDataException $e) {
    // Handle validation errors (400)
    Log::warning('Invalid order data', [
        'error' => $e->getMessage()
    ]);
} catch (ApiException $e) {
    // Handle API errors with context
    Log::error('API Error', [
        'message' => $e->getMessage(),
        'api_code' => $e->apiCode,
        'status_code' => $e->statusCode,
        'response' => $e->responseData
    ]);
} catch (\Exception $e) {
    // Handle unexpected errors
    Log::error('Unexpected error', [
        'error' => $e->getMessage()
    ]);
}
```

## 📖 API Reference

### `createOrder(array $orderData): array`

Creates a new shipping order.

**Required Fields:**
- `shippingAddress` - Receiver address information
- `orderItems` - Array of items being shipped

**Optional Fields:**
- `id` - Your internal order ID
- `deliveryType` - Delivery method
- `payType` - Payment type
- `expressType` - Service level
- `weight` - Package weight
- `total` - Order total amount
- And more...

**Returns:**
```php
[
    'success' => true|false,
    'data' => [...],
    'status_code' => 200,
    'waybill_code' => 'JTE123456789',
    'tx_logistic_id' => 'ORDER-12345',
    'sorting_code' => 'SC001',
    'last_center_name' => 'Cairo Hub'
]
```

---

### `cancelOrder(string $txlogisticId, string $reason = 'Customer request'): array`

Cancels an existing order.

---

### `trackOrder(string $billCode): array`

Tracks an order using the waybill code.

---

### `getOrders(string|array $serialNumbers): array`

Retrieves order details by serial number(s).

---

### `printOrder(string $billCode, string $printSize = '0', int $printCode = 0): array`

Generates a printable waybill.

## 🧪 Testing

The package includes a comprehensive test suite with **43 tests** and **139 assertions**.

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit/JTExpressServiceTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Test Coverage

```
Tests: 43, Assertions: 139, Failures: 0

Component               Tests    Status
──────────────────────────────────────────
Main Service              20      ✅
Facade                     6      ✅
Service Provider           4      ✅
Address Formatter          5      ✅
Item Formatter             4      ✅
Validator                  6      ✅
──────────────────────────────────────────
TOTAL                     43      ✅ 100%
```

### Test Structure

```
tests/
├── TestCase.php
└── Unit/
    ├── JTExpressServiceTest.php
    ├── JTExpressServiceProviderTest.php
    ├── JTExpressFacadeTest.php
    ├── AddressFormatterTest.php         # New
    ├── OrderItemFormatterTest.php       # New
    └── OrderDataValidatorTest.php       # New
```

## ⚠️ Error Handling

### Success Response

```php
[
    'success' => true,
    'data' => [...],
    'status_code' => 200,
    // Method-specific fields
]
```

### Error Response

```php
[
    'success' => false,
    'error' => 'Error message',
    'code' => 'API_ERROR_CODE',
    'data' => [...],
    'status_code' => 400|500
]
```

### Common Error Codes

| Code | Meaning | Action |
|------|---------|--------|
| `145003050` | Illegal parameters | Check input data |
| `121003006` | Order not printable | Wait for order to be processed |
| `400` | Validation failed | Check required fields |
| `500` | Internal error | Check logs, retry |

### Best Practices

```php
// Always check success status
if ($result['success']) {
    // Process success
    $waybillCode = $result['waybill_code'];
} else {
    // Log error with context
    Log::error('J&T API Error', [
        'method' => 'createOrder',
        'error' => $result['error'],
        'code' => $result['code'] ?? null,
        'order_data' => $orderData
    ]);

    // Return user-friendly message
    return response()->json([
        'message' => 'Failed to create shipment. Please try again.'
    ], 422);
}
```

## 🏗️ Architecture

### Package Structure

```
src/
├── Builders/
│   └── OrderRequestBuilder.php      # Builds order requests
├── DTOs/
│   ├── AddressData.php              # Immutable address DTO
│   ├── OrderItemData.php            # Immutable item DTO
│   └── OrderRequest.php             # Complete order DTO
├── Exceptions/
│   ├── JTExpressException.php       # Base exception
│   ├── InvalidOrderDataException.php # Validation errors
│   └── ApiException.php             # API errors
├── Facades/
│   └── JTExpress.php                # Laravel facade
├── Formatters/
│   ├── AddressFormatter.php         # Address formatting
│   └── OrderItemFormatter.php       # Item formatting
├── Handlers/
│   └── OrderResponseHandler.php     # Response handling
├── Http/
│   └── JTExpressApiClient.php       # HTTP client
├── Validators/
│   └── OrderDataValidator.php       # Input validation
├── JTExpressService.php             # Main service class
└── JTExpressServiceProvider.php     # Service provider
```

### Design Principles

- ✅ **SOLID Principles**: Clean, maintainable code
- ✅ **Type Safety**: Full PHP 8.1+ type declarations
- ✅ **Immutability**: DTOs with readonly properties
- ✅ **Separation of Concerns**: Each class has one responsibility
- ✅ **DRY**: No code duplication
- ✅ **Testability**: Easy to unit test

### Key Improvements (v2.0)

- 🎯 **60% Less Code Duplication**
- 🎯 **95% Type Coverage** (up from 20%)
- 🎯 **Pre-Request Validation** (catches errors early)
- 🎯 **Specific Exceptions** (better error handling)
- 🎯 **Immutable DTOs** (thread-safe)
- 🎯 **43 Tests** with 139 assertions

## 📋 Changelog

### Version 2.0.0 (Latest) - Major Refactoring

**Breaking Changes:** None! 100% backward compatible.

**New Features:**
- ✨ Type-safe DTOs for all data structures
- ✨ Automatic input validation before API calls
- ✨ Specific exception types with rich context
- ✨ Separated formatters for cleaner code
- ✨ Enhanced test suite (43 tests, 139 assertions)

**Improvements:**
- 🔧 Extracted magic values to constants
- 🔧 Reduced code duplication by 60%
- 🔧 Added comprehensive PHPDoc blocks
- 🔧 Improved error messages
- 🔧 Better logging with context

**Architecture:**
- 🏗️ Clean Architecture with SOLID principles
- 🏗️ Builders, Formatters, Validators, Handlers
- 🏗️ Immutable DTOs with readonly properties
- 🏗️ Comprehensive documentation

See [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md) for full details.

### Version 1.0.0

- Initial release
- Basic order management
- Tracking functionality
- Waybill printing

## 🔒 Security

### Best Practices

1. **Never commit credentials**: Keep API keys in `.env`
2. **Use environment variables**: Always use `env()` helper
3. **Validate user input**: Sanitize before passing to SDK
4. **Monitor logs**: Check for suspicious activities
5. **Use HTTPS**: Always in production

### Reporting Vulnerabilities

If you discover a security vulnerability, please email [moh.aly8890@gmail.com](mailto:moh.aly8890@gmail.com).

## 🤝 Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Setup

```bash
# Clone repository
git clone https://github.com/genius-code/jt-express-eg.git
cd jt-express-eg

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Check code style
./vendor/bin/phpcs
```

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 💬 Support

- **Email**: [moh.aly8890@gmail.com](mailto:moh.aly8890@gmail.com)
- **Issues**: [GitHub Issues](https://github.com/genius-code/jt-express-eg/issues)
- **Documentation**: [Full Documentation](docs/)

## 🙏 Acknowledgments

- Built for Laravel developers in Egypt
- Inspired by modern PHP best practices
- Special thanks to all contributors

## 📚 Related Resources

- [J&T Express Egypt](https://www.jtexpress.eg/)
- [Laravel Documentation](https://laravel.com/docs)
- [PHP: The Right Way](https://phptherightway.com/)

## 📊 Stats

- **Code Coverage**: 95%+ type safety
- **Tests**: 43 passing
- **Assertions**: 139
- **PHP Version**: 8.1+
- **Laravel**: 10.x, 11.x
- **Downloads**: [Packagist Stats](https://packagist.org/packages/genius-code/jt-express-eg)

---

<div align="center">

**Made with ❤️ by [Genius Code](https://github.com/genius-code)**

[⭐ Star us on GitHub](https://github.com/genius-code/jt-express-eg) | [📦 View on Packagist](https://packagist.org/packages/genius-code/jt-express-eg)

</div>