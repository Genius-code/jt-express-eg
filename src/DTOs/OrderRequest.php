<?php

namespace GeniusCode\JTExpressEg\DTOs;

class OrderRequest
{
    /**
     * @param OrderItemData[] $items
     */
    public function __construct(
        public readonly string $customerCode,
        public readonly string $digest,
        public readonly string $txlogisticId,
        public readonly AddressData $receiver,
        public readonly AddressData $sender,
        public readonly array $items,
        public readonly string $deliveryType = '04',
        public readonly string $payType = 'PP_PM',
        public readonly string $expressType = 'EZ',
        public readonly string $serviceType = '01',
        public readonly string $goodsType = 'ITN1',
        public readonly string $priceCurrency = 'EGP',
        public readonly string $network = '',
        public readonly float $length = 0,
        public readonly float $width = 10,
        public readonly float $height = 0,
        public readonly float $weight = 1,
        public readonly ?string $sendStartTime = null,
        public readonly ?string $sendEndTime = null,
        public readonly string $itemsValue = '',
        public readonly string $remark = '',
        public readonly string $invoceNumber = '',
        public readonly string $packingNumber = '',
        public readonly string $batchNumber = '',
        public readonly string $billCode = '',
        public readonly int $operateType = 1,
        public readonly string $orderType = '1',
        public readonly string $expectDeliveryStartTime = '',
        public readonly string $expectDeliveryEndTime = '',
        public readonly string $totalQuantity = '1',
        public readonly string $offerFee = '0'
    ) {}

    public function toArray(): array
    {
        $data = [
            'customerCode' => $this->customerCode,
            'digest' => $this->digest,
            'deliveryType' => $this->deliveryType,
            'payType' => $this->payType,
            'expressType' => $this->expressType,
            'network' => $this->network,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'sendStartTime' => $this->sendStartTime,
            'sendEndTime' => $this->sendEndTime,
            'itemsValue' => $this->itemsValue,
            'remark' => $this->remark,
            'invoceNumber' => $this->invoceNumber,
            'packingNumber' => $this->packingNumber,
            'batchNumber' => $this->batchNumber,
            'txlogisticId' => $this->txlogisticId,
            'billCode' => $this->billCode,
            'operateType' => $this->operateType,
            'orderType' => $this->orderType,
            'serviceType' => $this->serviceType,
            'expectDeliveryStartTime' => $this->expectDeliveryStartTime,
            'expectDeliveryEndTime' => $this->expectDeliveryEndTime,
            'goodsType' => $this->goodsType,
            'totalQuantity' => $this->totalQuantity,
            'offerFee' => $this->offerFee,
            'priceCurrency' => $this->priceCurrency,
            'receiver' => $this->receiver->toArray(),
            'sender' => $this->sender->toArray(),
            'items' => array_map(fn(OrderItemData $item) => $item->toArray(), $this->items),
        ];

        // Filter out empty values
        return array_filter($data, fn($value) => $value !== '' && $value !== null);
    }
}