<?php

namespace Appleera1\JtExpressEg\DTOs;

class OrderItemData
{
    public function __construct(
        public readonly string $itemName,
        public readonly int $number,
        public readonly string $itemValue,
        public readonly string $englishName = '',
        public readonly string $chineseName = '',
        public readonly string $itemType = 'ITN1',
        public readonly string $priceCurrency = 'EGP',
        public readonly string $itemUrl = '',
        public readonly string $desc = 'Order Item'
    ) {}

    public function toArray(): array
    {
        return [
            'itemName' => $this->itemName,
            'englishName' => $this->englishName,
            'chineseName' => $this->chineseName,
            'number' => $this->number,
            'itemType' => $this->itemType,
            'priceCurrency' => $this->priceCurrency,
            'itemValue' => $this->itemValue,
            'itemUrl' => $this->itemUrl,
            'desc' => $this->desc,
        ];
    }
}