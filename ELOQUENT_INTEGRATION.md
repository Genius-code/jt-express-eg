# Integrating with Eloquent Models

While this package is designed to be data-source agnostic and works with simple PHP arrays out of the box, you can easily create a wrapper service to integrate it seamlessly with your application's Eloquent models. This guide provides an example of how to achieve this.

## The Goal

Our goal is to create a service that can accept an `App\Models\Order` object, convert it to the array format required by the `JTExpressService`, call the service, and handle responses—all while providing application-specific logging and exception handling.

## 1. Define a Shipping Service Contract (Optional but Recommended)

First, define an interface for your application's shipping services. This allows you to depend on a contract rather than a concrete implementation, making it easy to swap out services in the future.

**`app/Contracts/ShippingServiceInterface.php`**
```php
<?php

namespace App\Contracts;

use App\Models\Order;

interface ShippingServiceInterface
{
    /**
     * Create a shipping order.
     *
     * @param array|Order $orderData
     * @return array
     */
    public function createOrder(array|Order $orderData): array;

    /**
     * Update a shipping order.
     *
     * @param array|Order $orderData
     * @return array
     */
    public function updateOrder(array|Order $orderData): array;

    /**
     * Cancel a shipping order.
     *
     * @param string $txlogisticId
     * @return array
     */
    public function cancelOrder(string $txlogisticId): array;

    /**
     * Track a shipping order.
     *
     * @param string $billCode
     * @return array
     */
    public function trackOrder(string $billCode): array;
}
```

## 2. Create Custom Exceptions (Optional)

Create your own exceptions to decouple your application from the package's exceptions. This gives you more control over error handling and logging.

**`app/Exceptions/InvalidShippingOrderException.php`**
```php
<?php

namespace App\Exceptions;

class InvalidShippingOrderException extends \Exception
{
    // You can add custom factory methods here
}
```

**`app/Exceptions/ShippingServiceException.php`**
```php
<?php

namespace App\Exceptions;

class ShippingServiceException extends \Exception
{
    // You can add custom factory methods here
}
```

## 3. Build the Wrapper Service

Now, create the wrapper service that implements your `ShippingServiceInterface`. This service will inject the core `GeniusCode\JTExpressEg\JTExpressService` and use it to perform the actual API calls.

**`app/Services/MyJTExpressWrapperService.php`**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ShippingServiceInterface;
use App\Exceptions\InvalidShippingOrderException;
use App\Exceptions\ShippingServiceException;
use App\Models\Order; // Your Eloquent Order model
use GeniusCode\JTExpressEg\Exceptions\InvalidOrderDataException;
use GeniusCode\JTExpressEg\Exceptions\JTExpressException;
use GeniusCode\JTExpressEg\JTExpressService as JTExpressClient;
use Illuminate\Support\Facades\Log;

class MyJTExpressWrapperService implements ShippingServiceInterface
{
    public function __construct(
        private readonly JTExpressClient $jtExpressClient
    ) {}

    public function createOrder(array|Order $orderData): array
    {
        try {
            $orderArray = $orderData instanceof Order
                ? $this->convertOrderToArray($orderData)
                : $orderData;

            Log::info('JTExpress Wrapper: Creating order', [
                'order_id' => $orderData instanceof Order ? $orderData->id : ($orderData['id'] ?? 'unknown'),
            ]);

            $response = $this->jtExpressClient->createOrder($orderArray);

            // Add your own logging and handling logic
            if (!($response['success'] ?? false)) {
                Log::warning('JTExpress Wrapper: Order creation failed', ['error' => $response['error'] ?? 'Unknown']);
            }

            return $response;

        } catch (InvalidOrderDataException $e) {
            throw new InvalidShippingOrderException("Invalid order data: {$e->getMessage()}", 0, $e);
        } catch (JTExpressException $e) {
            throw new ShippingServiceException("API Error: {$e->getMessage()}", 0, $e);
        } catch (\Throwable $e) {
            Log::error('JTExpress Wrapper: Unexpected error', ['message' => $e->getMessage()]);
            throw new ShippingServiceException("An unexpected error occurred.", 0, $e);
        }
    }

    public function updateOrder(array|Order $orderData): array
    {
        try {
            $orderArray = $orderData instanceof Order
                ? $this->convertOrderToArray($orderData)
                : $orderData;

            Log::info('JTExpress Wrapper: Updating order', [
                'order_id' => $orderData instanceof Order ? $orderData->id : ($orderData['id'] ?? 'unknown'),
            ]);

            $response = $this->jtExpressClient->updateOrder($orderArray);

            // Add your own logging and handling logic
            if (!($response['success'] ?? false)) {
                Log::warning('JTExpress Wrapper: Order update failed', ['error' => $response['error'] ?? 'Unknown']);
            }

            return $response;

        } catch (InvalidOrderDataException $e) {
            throw new InvalidShippingOrderException("Invalid order data: {$e->getMessage()}", 0, $e);
        } catch (JTExpressException $e) {
            throw new ShippingServiceException("API Error: {$e->getMessage()}", 0, $e);
        } catch (\Throwable $e) {
            Log::error('JTExpress Wrapper: Unexpected error', ['message' => $e->getMessage()]);
            throw new ShippingServiceException("An unexpected error occurred.", 0, $e);
        }
    }

    public function cancelOrder(string $txlogisticId): array
    {
        // ... similar implementation for cancelOrder ...
    }

    public function trackOrder(string $billCode): array
    {
        // ... similar implementation for trackOrder ...
    }

    /**
     * Convert an Order model to the array format expected by the package.
     *
     * @param Order $order
     * @return array
     * @throws InvalidShippingOrderException
     */
    private function convertOrderToArray(Order $order): array
    {
        // Ensure required relationships are loaded to avoid N+1 queries
        $order->loadMissing(['shippingAddress.city', 'shippingAddress.state', 'orderItems.product']);

        if (!$order->shippingAddress) {
            throw new InvalidShippingOrderException('Shipping address is missing.');
        }

        if ($order->orderItems->isEmpty()) {
            throw new InvalidShippingOrderException('Order items are missing.');
        }

        return [
            'id' => $order->id,
            'total' => $order->total,
            'shippingAddress' => [
                'first_name' => $order->shippingAddress->first_name,
                'phone' => $order->shippingAddress->phone,
                'address_line1' => $order->shippingAddress->address_line1,
                'city' => ['name' => $order->shippingAddress->city->name ?? ''],
                'state' => ['name' => $order->shippingAddress->state->name ?? ''],
            ],
            'orderItems' => $order->orderItems->map(fn($item) => [
                'product' => [
                    'name' => $item->product->name ?? 'N/A',
                ],
                'quantity' => $item->quantity,
                'price_at_purchase' => $item->price_at_purchase,
            ])->toArray(),
        ];
    }
}
```

## 4. Register Your Service

Finally, register your wrapper service in a service provider. You can bind it to the `ShippingServiceInterface` contract.

**`app/Providers/AppServiceProvider.php`**
```php
use App\Contracts\ShippingServiceInterface;
use App\Services\MyJTExpressWrapperService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ShippingServiceInterface::class, function ($app) {
            // The core JTExpressService is already registered by its own provider
            return new MyJTExpressWrapperService($app->make(\GeniusCode\JTExpressEg\JTExpressService::class));
        });
    }
}
```

## 5. Use It in Your Application

Now you can inject `ShippingServiceInterface` into your controllers or other services and use it with your `Order` models.

```php
use App\Contracts\ShippingServiceInterface;
use App\Models\Order;

class MyController
{
    public function __construct(
        private ShippingServiceInterface $shippingService
    ) {}

    public function shipOrder(int $orderId)
    {
        $order = Order::findOrFail($orderId);

        $result = $this->shippingService->createOrder($order);

        if ($result['success']) {
            // ...
        }
    }

    public function updateShipment(int $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Modify order data as needed for update
        $order->deliveryType = '02'; // Example change

        $result = $this->shippingService->updateOrder($order);

        if ($result['success']) {
            // ...
        }
    }
}
```

This approach keeps your application decoupled from the package's internal implementation details and provides a clean, maintainable way to interact with the J&T Express API using your own data structures.
