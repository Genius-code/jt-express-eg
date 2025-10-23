# J&T Express Wrapper Service Guide

This guide explains how to use the improved JTExpressService wrapper in your Laravel application.

## Overview

The wrapper service provides a clean, type-safe interface to the J&T Express Egypt package with proper error handling, validation, and logging.

## Features

✅ **Type Safety**: Full PHP 8.1+ type declarations with `declare(strict_types=1)`
✅ **Validation**: Pre-request validation to catch errors before API calls
✅ **Error Handling**: Custom exceptions for better error management
✅ **Logging**: Comprehensive logging for debugging and monitoring
✅ **Defensive Programming**: Validates relationships are loaded (prevents N+1 queries)
✅ **Interface-based**: Easy to mock for testing and swap implementations

## Installation

### 1. Files Created

The following files have been created in your application:

```
app/
├── Contracts/
│   └── ShippingServiceInterface.php      # Interface for shipping services
├── Exceptions/
│   ├── InvalidShippingOrderException.php  # Validation errors
│   └── ShippingServiceException.php       # Service errors
└── Services/
    └── JTExpressService.php               # Main wrapper service
```

### 2. Configuration (Optional)

Add to `config/services.php`:

```php
return [
    // ... other services

    'jt_express' => [
        'default_country' => env('JT_EXPRESS_DEFAULT_COUNTRY', 'EG'),
    ],
];
```

### 3. Service Provider Registration (Optional)

If you want to use dependency injection, bind the interface in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

use App\Contracts\ShippingServiceInterface;
use App\Services\JTExpressService;
use GeniusCode\JTExpressEg\JTExpressService as JTExpressClient;

public function register(): void
{
    $this->app->bind(ShippingServiceInterface::class, function ($app) {
        return new JTExpressService(
            new JTExpressClient()
        );
    });
}
```

## Usage Examples

### Example 1: Create Order with Order Model

```php
use App\Services\JTExpressService;
use App\Exceptions\InvalidShippingOrderException;
use App\Exceptions\ShippingServiceException;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(
        private JTExpressService $jtExpressService
    ) {}

    public function createShipment(Order $order)
    {
        try {
            // IMPORTANT: Load relationships before passing to service
            $order->load([
                'shippingAddress.city',
                'shippingAddress.state',
                'shippingAddress.country',
                'orderItems.product'
            ]);

            // Create J&T Express order
            $response = $this->jtExpressService->createOrder($order);

            if ($response['success']) {
                // Save shipping details to order
                $order->update([
                    'jt_txlogistic_id' => $response['data']['txlogisticId'] ?? null,
                    'jt_bill_code' => $response['data']['billCode'] ?? null,
                    'shipping_status' => 'shipped',
                ]);

                return response()->json([
                    'message' => 'Shipment created successfully',
                    'data' => $response['data']
                ]);
            }

            return response()->json([
                'message' => 'Failed to create shipment',
                'error' => $response['error']
            ], 400);

        } catch (InvalidShippingOrderException $e) {
            // Validation error - missing data or relationships
            return response()->json([
                'message' => 'Invalid order data',
                'error' => $e->getMessage()
            ], 422);

        } catch (ShippingServiceException $e) {
            // Service error - API communication failed
            return response()->json([
                'message' => 'Shipping service error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Example 2: Create Order with Array

```php
public function createCustomShipment()
{
    try {
        $orderData = [
            'id' => 'ORDER-' . uniqid(),
            'total' => 500,
            'shippingAddress' => [
                'first_name' => 'Ahmed',
                'last_name' => 'Mohamed',
                'phone' => '01012345678',
                'address_line1' => '123 Main St, Nasr City',
                'city' => ['name' => 'Cairo'],
                'state' => ['name' => 'Cairo'],
                'country' => ['code' => 'EG']
            ],
            'orderItems' => [
                [
                    'product' => [
                        'name' => 'Product Name',
                        'description' => 'Product Description'
                    ],
                    'quantity' => 2,
                    'price_at_purchase' => 250
                ]
            ]
        ];

        $response = $this->jtExpressService->createOrder($orderData);

        return response()->json($response);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### Example 3: Cancel Order

```php
public function cancelShipment(Order $order)
{
    try {
        if (empty($order->jt_txlogistic_id)) {
            return response()->json([
                'error' => 'No J&T Express shipment found for this order'
            ], 404);
        }

        $response = $this->jtExpressService->cancelOrder(
            $order->jt_txlogistic_id,
            'Customer requested cancellation'
        );

        if ($response['success']) {
            $order->update(['shipping_status' => 'cancelled']);
        }

        return response()->json($response);

    } catch (ShippingServiceException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### Example 4: Track Order

```php
public function trackShipment(Order $order)
{
    try {
        if (empty($order->jt_bill_code)) {
            return response()->json([
                'error' => 'No tracking code found for this order'
            ], 404);
        }

        $trackingData = $this->jtExpressService->trackOrder($order->jt_bill_code);

        return response()->json($trackingData);

    } catch (ShippingServiceException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### Example 5: Print Waybill

```php
public function printWaybill(Order $order)
{
    try {
        if (empty($order->jt_bill_code)) {
            return response()->json([
                'error' => 'No waybill code found for this order'
            ], 404);
        }

        $printData = $this->jtExpressService->printOrder($order->jt_bill_code);

        if ($printData['success']) {
            // $printData['data'] contains base64 encoded PDF
            return response()->json([
                'pdf' => $printData['data']
            ]);
        }

        return response()->json([
            'error' => $printData['error']
        ], 400);

    } catch (ShippingServiceException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### Example 6: Get Order Details

```php
public function getOrderDetails(Order $order)
{
    try {
        // Can pass single ID or array of IDs
        $response = $this->jtExpressService->getOrders($order->id);

        return response()->json($response);

    } catch (ShippingServiceException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
```

## Important Notes

### 1. Relationship Loading ⚠️

**CRITICAL**: Always load required relationships before creating an order:

```php
// ✅ CORRECT
$order = Order::with([
    'shippingAddress.city',
    'shippingAddress.state',
    'orderItems.product'
])->find($id);

$response = $jtExpressService->createOrder($order);

// ❌ WRONG - Will throw InvalidShippingOrderException
$order = Order::find($id);
$response = $jtExpressService->createOrder($order); // Relationships not loaded!
```

### 2. Error Handling

The service throws two main exception types:

- **`InvalidShippingOrderException`**: Validation errors (missing data, invalid format)
- **`ShippingServiceException`**: Service errors (API communication, network issues)

Always catch these exceptions in your controllers:

```php
try {
    $response = $jtExpressService->createOrder($order);
} catch (InvalidShippingOrderException $e) {
    // Handle validation errors (422)
} catch (ShippingServiceException $e) {
    // Handle service errors (500)
}
```

### 3. Response Format

All methods return an array with the following structure:

```php
// Success response
[
    'success' => true,
    'data' => [...],
    'status_code' => 200
]

// Error response
[
    'success' => false,
    'error' => 'Error message',
    'status_code' => 400
]
```

### 4. Logging

The service automatically logs:
- Order creation attempts
- Success/failure of operations
- Validation errors
- API errors

Check your logs at `storage/logs/laravel.log` for detailed information.

## Testing

### Mocking the Service

```php
use App\Contracts\ShippingServiceInterface;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_can_create_shipment()
    {
        // Mock the shipping service
        $mock = $this->mock(ShippingServiceInterface::class);

        $mock->shouldReceive('createOrder')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'txlogisticId' => 'TX123456',
                    'billCode' => 'JT123456789'
                ]
            ]);

        // Your test code here
    }
}
```

## Migration from Old Code

If you were using the old wrapper without validation:

### Old Code
```php
public function createOrder($orderData)
{
    if (is_object($orderData) && get_class($orderData) === 'App\Models\Order') {
        $orderData = $this->convertOrderToArray($orderData);
    }
    return $this->jtExpressService->createOrder($orderData);
}
```

### New Code
```php
public function createOrder(array|Order $orderData): array
{
    // Automatically validates relationships and data
    // Throws proper exceptions with meaningful messages
    // Logs all operations for debugging
    return $this->jtExpressService->createOrder($orderData);
}
```

## Key Improvements

1. **Type Safety**: Uses `instanceof` instead of `get_class()`
2. **Validation**: Checks relationships are loaded before accessing
3. **Error Handling**: Custom exceptions with static factory methods
4. **Logging**: Comprehensive logging throughout the process
5. **Constants**: Extracted default values to class constants
6. **Documentation**: Full PHPDoc with examples
7. **Interface**: Implements `ShippingServiceInterface` for better DI

## Troubleshooting

### "Order must have the following relationships loaded: shippingAddress, orderItems"

**Solution**: Load relationships before calling the service:
```php
$order->load(['shippingAddress', 'orderItems.product']);
```

### "Shipping address is missing required fields: Phone number"

**Solution**: Ensure shipping address has all required fields:
- first_name
- phone
- address_line1

### "Order must have at least one order item"

**Solution**: Make sure the order has items before creating shipment.

## Support

For package-specific issues, see: https://github.com/genius-code/jt-express-eg