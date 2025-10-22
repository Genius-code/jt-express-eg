# Upgrade Guide - Refactored Version

## Overview
The JT Express Egypt SDK has been completely refactored for better code quality, type safety, and maintainability. **The public API remains 100% backward compatible.**

## No Breaking Changes

If you're using the facade or service class directly, **no changes are required**:

```php
// This still works exactly the same way
use Appleera1\JtExpressEg\Facades\JTExpress;

$result = JTExpress::createOrder([
    'shippingAddress' => $address,
    'orderItems' => $items,
    // ... other fields
]);
```

## What's New

### 1. Better Validation

The refactored version validates data **before** making API calls:

```php
// Will throw InvalidOrderDataException if validation fails
try {
    $result = JTExpress::createOrder($orderData);
} catch (\Appleera1\JtExpressEg\Exceptions\InvalidOrderDataException $e) {
    // Handle validation error
    // e.g., missing shippingAddress or orderItems
}
```

### 2. Specific Exceptions

You can now catch specific exception types:

```php
use Appleera1\JtExpressEg\Exceptions\InvalidOrderDataException;
use Appleera1\JtExpressEg\Exceptions\ApiException;

try {
    $result = JTExpress::createOrder($orderData);
} catch (InvalidOrderDataException $e) {
    // Handle validation errors (400)
    Log::warning("Invalid order data: " . $e->getMessage());
} catch (ApiException $e) {
    // Handle API errors with more context
    Log::error("API Error", [
        'code' => $e->apiCode,
        'status' => $e->statusCode,
        'response' => $e->responseData
    ]);
} catch (\Exception $e) {
    // Handle unexpected errors (500)
}
```

### 3. Using Individual Components (Advanced)

If you need fine-grained control, you can use components directly:

#### Address Formatting
```php
use Appleera1\JtExpressEg\Formatters\AddressFormatter;

$formatter = new AddressFormatter();
$receiverAddress = $formatter->formatReceiver($shippingAddress);
$senderAddress = $formatter->formatSender();
```

#### Order Item Formatting
```php
use Appleera1\JtExpressEg\Formatters\OrderItemFormatter;

$itemFormatter = new OrderItemFormatter();
$formattedItems = $itemFormatter->format($orderItems);
```

#### Validation
```php
use Appleera1\JtExpressEg\Validators\OrderDataValidator;

$validator = new OrderDataValidator();
try {
    $validator->validate($orderData);
    // Data is valid
} catch (InvalidOrderDataException $e) {
    // Data is invalid
}
```

## Improved Type Safety

All methods now have proper type declarations:

```php
// Before (no types)
public function createOrder($orderData)

// After (full type safety)
public function createOrder(array $orderData): array
```

This means:
- Better IDE autocomplete
- Compile-time error detection
- Self-documenting code

## New Constants

Magic values have been extracted to constants. If you need to reference defaults:

```php
use Appleera1\JtExpressEg\Builders\OrderRequestBuilder;

// Access constants if needed
OrderRequestBuilder::DEFAULT_DELIVERY_TYPE; // '04'
OrderRequestBuilder::DEFAULT_PAY_TYPE; // 'PP_PM'
// etc.
```

## Testing Improvements

The refactored structure makes testing much easier:

```php
// Example: Test address formatting in isolation
public function test_address_formatter()
{
    $formatter = new AddressFormatter();

    $result = $formatter->formatReceiver([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '01234567890',
        // ...
    ]);

    $this->assertInstanceOf(AddressData::class, $result);
    $this->assertEquals('John Doe', $result->name);
}
```

## Configuration (No Changes Required)

Your existing config file (`config/jt-express.php`) works without modification:

```php
return [
    'apiAccount' => env('JT_EXPRESS_API_ACCOUNT'),
    'privateKey' => env('JT_EXPRESS_PRIVATE_KEY'),
    'customerCode' => env('JT_EXPRESS_CUSTOMER_CODE'),
    'customerPwd' => env('JT_EXPRESS_CUSTOMER_PWD'),

    'sender' => [
        'name' => env('JT_EXPRESS_SENDER_NAME'),
        'mobile' => env('JT_EXPRESS_SENDER_MOBILE'),
        // ...
    ]
];
```

## Response Format (Unchanged)

Response format remains exactly the same:

```php
// Success response
[
    'success' => true,
    'data' => [...],
    'status_code' => 200,
    'waybill_code' => 'JT123456789',
    'tx_logistic_id' => 'ORDER0001234567',
    'sorting_code' => 'SORT123',
    'last_center_name' => 'Cairo Hub'
]

// Error response
[
    'success' => false,
    'error' => 'Error message',
    'code' => '145003050',
    'data' => [...],
    'status_code' => 400
]
```

## Error Response Improvements

Error responses now include validation errors **before** API calls:

```php
// Before: Only API errors
[
    'success' => false,
    'error' => 'API error message',
    'status_code' => 400
]

// After: Also includes validation errors
[
    'success' => false,
    'error' => 'Shipping address is required for order creation',
    'status_code' => 400
]
```

## Deprecations

None! Everything is backward compatible.

## Recommended Changes

While not required, consider these improvements:

### 1. Update Exception Handling

```php
// Old way (still works)
$result = JTExpress::createOrder($data);
if (!$result['success']) {
    // handle error
}

// Better way (recommended)
try {
    $result = JTExpress::createOrder($data);
    // handle success
} catch (InvalidOrderDataException $e) {
    // handle validation error
} catch (ApiException $e) {
    // handle API error
}
```

### 2. Add Type Hints

```php
// If you're wrapping the SDK in your own service

// Before
public function placeOrder($orderData)
{
    return JTExpress::createOrder($orderData);
}

// After (better)
public function placeOrder(array $orderData): array
{
    return JTExpress::createOrder($orderData);
}
```

## File Structure

New files added (you don't need to interact with these directly):

```
src/
├── Builders/OrderRequestBuilder.php          # New
├── DTOs/                                      # New
│   ├── AddressData.php
│   ├── OrderItemData.php
│   └── OrderRequest.php
├── Exceptions/                                # New
│   ├── ApiException.php
│   ├── InvalidOrderDataException.php
│   └── JTExpressException.php
├── Formatters/                                # New
│   ├── AddressFormatter.php
│   └── OrderItemFormatter.php
├── Handlers/OrderResponseHandler.php          # New
├── Http/JTExpressApiClient.php                # New
├── Validators/OrderDataValidator.php          # New
├── JTExpressService.php                       # Refactored
└── JTExpressServiceProvider.php               # Unchanged
```

## Performance

- **No performance degradation** - PHP 8.1+ opcache handles additional classes efficiently
- **Potentially faster** - validation prevents unnecessary API calls
- **Better memory usage** - readonly properties in DTOs

## Need Help?

If you encounter any issues:

1. Check that `shippingAddress` and `orderItems` are provided
2. Review validation error messages
3. Check logs for detailed error information
4. Ensure PHP >= 8.1

## Summary

✅ **Zero breaking changes**
✅ **Full backward compatibility**
✅ **Optional improvements available**
✅ **Better error handling**
✅ **Improved type safety**
✅ **Same performance**

You can upgrade immediately without any code changes!