# JT Express Egypt SDK - Architecture

## Overview
This document describes the refactored architecture of the JT Express Egypt Laravel SDK.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        User Application                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    JTExpress Facade                              │
│                  (Laravel Service Container)                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    JTExpressService                              │
│                   (Main Service Class)                           │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ • createOrder()                                           │  │
│  │ • cancelOrder()                                           │  │
│  │ • trackOrder()                                            │  │
│  │ • getOrders()                                             │  │
│  │ • printOrder()                                            │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────┬──────────┬──────────┬──────────┬───────────┬──────────┘
         │          │          │          │           │
         ▼          ▼          ▼          ▼           ▼
    ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐
    │Validator│ │Builders│ │Formatter││Handlers│ │API Client│
    └────────┘ └────────┘ └────────┘ └────────┘ └──────────┘
```

## Component Architecture

### 1. Entry Points

```
JTExpress Facade
    └─> JTExpressService
        ├─> Public API Methods
        │   ├─ createOrder()
        │   ├─ cancelOrder()
        │   ├─ trackOrder()
        │   ├─ getOrders()
        │   └─ printOrder()
        └─> Protected Helper Methods
            ├─ calculateBizContentDigest()
            ├─ calculateHeaderDigest()
            ├─ getHeaders()
            └─ generateTimestamp()
```

### 2. Request Flow (createOrder)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. User calls JTExpress::createOrder($orderData)                │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. OrderDataValidator validates input                           │
│    • Checks shippingAddress exists                              │
│    • Checks orderItems exists                                   │
│    • Validates optional fields                                  │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. Formatters transform data                                    │
│    ┌─────────────────────────────────────────────────┐         │
│    │ AddressFormatter::formatReceiver()              │         │
│    │   ├─> Handles empty, object, or array input    │         │
│    │   └─> Returns AddressData DTO                  │         │
│    └─────────────────────────────────────────────────┘         │
│    ┌─────────────────────────────────────────────────┐         │
│    │ AddressFormatter::formatSender()                │         │
│    │   └─> Returns AddressData DTO from config      │         │
│    └─────────────────────────────────────────────────┘         │
│    ┌─────────────────────────────────────────────────┐         │
│    │ OrderItemFormatter::format()                    │         │
│    │   └─> Returns OrderItemData[] DTOs             │         │
│    └─────────────────────────────────────────────────┘         │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. OrderRequestBuilder builds request                           │
│    • Combines all data                                          │
│    • Applies defaults from constants                            │
│    • Returns OrderRequest DTO                                   │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Request preparation                                          │
│    • Convert DTO to array                                       │
│    • JSON encode                                                │
│    • Calculate digests                                          │
│    • Build headers                                              │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. JTExpressApiClient sends request                             │
│    • Logs request details                                       │
│    • Makes HTTP call                                            │
│    • Logs response                                              │
│    • Returns Response object                                    │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. OrderResponseHandler processes response                      │
│    • Checks success/failure                                     │
│    • Builds standardized response array                         │
│    • Returns to caller                                          │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 8. Response returned to user                                    │
└─────────────────────────────────────────────────────────────────┘
```

## Layer Responsibilities

### Presentation Layer
- **JTExpress Facade**: Laravel facade for convenient static access
- **JTExpressService**: Main service class exposing public API

### Business Logic Layer
- **Validators**: Data validation before processing
- **Builders**: Constructs complex request objects
- **Formatters**: Transform user data to API format
- **Handlers**: Process API responses

### Infrastructure Layer
- **HTTP Client**: Handles HTTP communication
- **DTOs**: Type-safe data transfer objects
- **Exceptions**: Custom exception hierarchy

## Data Flow Diagram

```
User Data (Array/Object)
    │
    ├─> Validator ──> Validation Errors? ──> InvalidOrderDataException
    │                          │
    │                          No
    ▼                          │
Formatters                     │
    ├─> AddressFormatter ──────┤
    ├─> OrderItemFormatter ────┤
    │                          │
    ▼                          │
DTOs (Typed Objects)           │
    ├─> AddressData            │
    ├─> OrderItemData[]        │
    │                          │
    ▼                          │
Builder                        │
    └─> OrderRequest DTO       │
            │                  │
            ▼                  │
    JSON Encoding              │
            │                  │
            ▼                  │
    API Client ─────> Request │
            │                  │
            ▼                  │
    HTTP Response              │
            │                  │
            ▼                  │
    Response Handler           │
            │                  │
            ▼                  │
    Standardized Array ────────┘
            │
            ▼
    Return to User
```

## Class Diagram

```
┌──────────────────────┐
│  JTExpressService    │
├──────────────────────┤
│ - apiAccount         │
│ - privateKey         │
│ - customerCode       │
│ - customerPwd        │
│ - baseUrl            │
│ - apiClient          │◄─────────┐
│ - responseHandler    │◄───┐     │
│ - addressFormatter   │◄─┐ │     │
│ - itemFormatter      │◄─│─│─┐   │
│ - validator          │◄─│─│─│─┐ │
├──────────────────────┤  │ │ │ │ │
│ + createOrder()      │  │ │ │ │ │
│ + cancelOrder()      │  │ │ │ │ │
│ + trackOrder()       │  │ │ │ │ │
│ + getOrders()        │  │ │ │ │ │
│ + printOrder()       │  │ │ │ │ │
└──────────────────────┘  │ │ │ │ │
                          │ │ │ │ │
         ┌────────────────┘ │ │ │ │
         │                  │ │ │ │
         ▼                  │ │ │ │
┌──────────────────────┐   │ │ │ │
│  AddressFormatter    │   │ │ │ │
├──────────────────────┤   │ │ │ │
│ + formatReceiver()   │   │ │ │ │
│ + formatSender()     │   │ │ │ │
│ - extractFromObject()│   │ │ │ │
│ - extractFromArray() │   │ │ │ │
└──────────────────────┘   │ │ │ │
         │                 │ │ │ │
         └─────────────────┘ │ │ │
                             │ │ │
         ┌───────────────────┘ │ │
         ▼                     │ │
┌──────────────────────┐      │ │
│  OrderItemFormatter  │      │ │
├──────────────────────┤      │ │
│ + format()           │      │ │
│ - formatFromObject() │      │ │
│ - formatFromArray()  │      │ │
└──────────────────────┘      │ │
                              │ │
         ┌────────────────────┘ │
         ▼                      │
┌──────────────────────┐       │
│ OrderResponseHandler │       │
├──────────────────────┤       │
│ + handle()           │       │
│ - isSuccessful()     │       │
│ - buildSuccess()     │       │
│ - buildError()       │       │
└──────────────────────┘       │
                               │
         ┌─────────────────────┘
         ▼
┌──────────────────────┐
│  OrderDataValidator  │
├──────────────────────┤
│ + validate()         │
│ + validateAddress()  │
│ + validateItems()    │
└──────────────────────┘

┌──────────────────────┐
│ JTExpressApiClient   │
├──────────────────────┤
│ - baseUrl            │
├──────────────────────┤
│ + createOrder()      │
│ - logRequest()       │
│ - logResponse()      │
└──────────────────────┘
```

## DTO Structure

```
AddressData (readonly)
├─ name
├─ mobile
├─ phone
├─ countryCode
├─ prov
├─ city
├─ area
├─ street
├─ building
├─ floor
├─ flats
├─ company
├─ mailBox
├─ postCode
├─ latitude
└─ longitude

OrderItemData (readonly)
├─ itemName
├─ number
├─ itemValue
├─ englishName
├─ chineseName
├─ itemType
├─ priceCurrency
├─ itemUrl
└─ desc

OrderRequest (readonly)
├─ customerCode
├─ digest
├─ txlogisticId
├─ receiver: AddressData
├─ sender: AddressData
├─ items: OrderItemData[]
├─ deliveryType
├─ payType
├─ expressType
└─ ... (30+ fields)
```

## Exception Hierarchy

```
Exception
    └─ JTExpressException (base)
        ├─ InvalidOrderDataException
        │   ├─ missingShippingAddress()
        │   ├─ missingOrderItems()
        │   └─ invalidField()
        └─ ApiException
            ├─ apiCode
            ├─ statusCode
            └─ responseData
```

## Design Patterns Used

1. **Facade Pattern**: JTExpress facade for simple API access
2. **Builder Pattern**: OrderRequestBuilder constructs complex requests
3. **Strategy Pattern**: Different formatters for different data types
4. **Data Transfer Object**: Immutable DTOs for type safety
5. **Single Responsibility**: Each class has one clear purpose
6. **Dependency Injection**: Ready for IoC container

## Extension Points

### Adding New Order Methods
```php
// 1. Add method to JTExpressApiClient
public function newMethod($data, $headers) { }

// 2. Add handler if needed
class NewMethodHandler { }

// 3. Add method to JTExpressService
public function newMethod($params) {
    // Use existing components
    $this->validator->validate($params);
    // ...
}
```

### Custom Formatters
```php
// Create your own formatter
class CustomAddressFormatter extends AddressFormatter {
    public function formatReceiver($address): AddressData {
        // Custom logic
    }
}

// Use dependency injection (future enhancement)
```

## Testing Strategy

```
Unit Tests
├─ Validators/
│   └─ OrderDataValidatorTest
├─ Formatters/
│   ├─ AddressFormatterTest
│   └─ OrderItemFormatterTest
├─ Builders/
│   └─ OrderRequestBuilderTest
└─ Handlers/
    └─ OrderResponseHandlerTest

Integration Tests
├─ JTExpressServiceTest
│   ├─ testCreateOrder()
│   ├─ testCancelOrder()
│   └─ ...
└─ JTExpressApiClientTest

Feature Tests
└─ OrderCreationFlowTest
```

## Performance Considerations

- **Lazy Loading**: Dependencies created in constructor
- **Immutable DTOs**: Readonly properties prevent accidental modifications
- **Early Validation**: Fail fast before making API calls
- **Opcache**: PHP 8.1+ handles class loading efficiently

## Security Features

- **Input Validation**: Before processing
- **Type Safety**: Prevents type juggling vulnerabilities
- **Digest Calculation**: Secure request signing
- **Exception Handling**: Prevents information leakage

## Backward Compatibility

All refactoring maintains 100% backward compatibility:
- Same public API
- Same response format
- Same configuration
- Same facade usage

## Future Enhancements

1. **Service Container Binding**: Dependency injection
2. **Event System**: Dispatch events at key points
3. **Rate Limiting**: Built into API client
4. **Retry Logic**: Automatic retry on failure
5. **Caching**: Cache frequently accessed data
6. **Queue Support**: Async order processing
7. **Webhooks**: Handle JT Express callbacks
8. **Logging Improvements**: Structured logging

---

This architecture provides a solid foundation for maintaining and extending the JT Express Egypt SDK.