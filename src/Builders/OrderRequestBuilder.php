<?php

namespace GeniusCode\JTExpressEg\Builders;

use GeniusCode\JTExpressEg\DTOs\AddressData;
use GeniusCode\JTExpressEg\DTOs\OrderItemData;
use GeniusCode\JTExpressEg\DTOs\OrderRequest;
use Carbon\Carbon;

class OrderRequestBuilder
{
    private const DEFAULT_DELIVERY_TYPE = '04';
    private const DEFAULT_PAY_TYPE = 'PP_PM';
    private const DEFAULT_EXPRESS_TYPE = 'EZ';
    private const DEFAULT_SERVICE_TYPE = '01';
    private const DEFAULT_GOODS_TYPE = 'ITN1';
    private const DEFAULT_OPERATE_TYPE = 1;
    private const DEFAULT_ORDER_TYPE = '1';
    private const DEFAULT_TOTAL_QUANTITY = '1';
    private const DEFAULT_OFFER_FEE = '0';
    private const CURRENCY = 'EGP';

    public function __construct(
        private readonly string $customerCode,
        private readonly string $digest
    ) {}

    public function build(array $orderData, AddressData $receiver, AddressData $sender, array $items): OrderRequest
    {
        return new OrderRequest(
            customerCode: $this->customerCode,
            digest: $this->digest,
            txlogisticId: $this->generateTxLogisticId($orderData),
            receiver: $receiver,
            sender: $sender,
            items: $items,
            deliveryType: $orderData['deliveryType'] ?? self::DEFAULT_DELIVERY_TYPE,
            payType: $orderData['payType'] ?? self::DEFAULT_PAY_TYPE,
            expressType: $orderData['expressType'] ?? self::DEFAULT_EXPRESS_TYPE,
            serviceType: $orderData['serviceType'] ?? self::DEFAULT_SERVICE_TYPE,
            goodsType: $orderData['goodsType'] ?? self::DEFAULT_GOODS_TYPE,
            priceCurrency: self::CURRENCY,
            network: $orderData['network'] ?? '',
            length: (float)($orderData['length'] ?? 0),
            width: (float)($orderData['width'] ?? 10),
            height: (float)($orderData['height'] ?? 0),
            weight: (float)($orderData['weight'] ?? 1),
            sendStartTime: $orderData['sendStartTime'] ?? Carbon::now()->format('Y-m-d H:i:s'),
            sendEndTime: $orderData['sendEndTime'] ?? Carbon::now()->addDay()->format('Y-m-d H:i:s'),
            itemsValue: (string)($orderData['total'] ?? ''),
            remark: $orderData['remark'] ?? '',
            invoceNumber: $orderData['invoiceNumber'] ?? '',
            packingNumber: $orderData['packingNumber'] ?? '',
            batchNumber: $orderData['batchNumber'] ?? '',
            billCode: $orderData['billCode'] ?? '',
            operateType: (int)($orderData['operateType'] ?? self::DEFAULT_OPERATE_TYPE),
            orderType: (string)($orderData['orderType'] ?? self::DEFAULT_ORDER_TYPE),
            expectDeliveryStartTime: $orderData['expectDeliveryStartTime'] ?? '',
            expectDeliveryEndTime: $orderData['expectDeliveryEndTime'] ?? '',
            totalQuantity: (string)($orderData['totalQuantity'] ?? self::DEFAULT_TOTAL_QUANTITY),
            offerFee: (string)($orderData['offerFee'] ?? self::DEFAULT_OFFER_FEE)
        );
    }

    private function generateTxLogisticId(array $orderData): string
    {
        if (isset($orderData['id']) && !empty($orderData['id'])) {
            return $orderData['id'];
        }

        return 'ORDER' . str_pad((string)rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    }
}