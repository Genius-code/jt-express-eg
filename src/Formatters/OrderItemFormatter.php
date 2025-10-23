<?php

namespace GeniusCode\JTExpressEg\Formatters;

use GeniusCode\JTExpressEg\DTOs\OrderItemData;

class OrderItemFormatter
{
    private const DEFAULT_ITEM_TYPE = 'ITN1';
    private const CURRENCY = 'EGP';
    private const DEFAULT_ITEM_NAME = 'Product';
    private const DEFAULT_DESCRIPTION = 'Order Item';

    /**
     * @param iterable $items
     * @return OrderItemData[]
     */
    public function format(iterable $items): array
    {
        $formattedItems = [];

        foreach ($items as $item) {
            $formattedItems[] = is_object($item)
                ? $this->formatFromObject($item)
                : $this->formatFromArray($item);
        }

        // Ensure at least one item exists
        if (empty($formattedItems)) {
            $formattedItems[] = $this->getDefaultItem();
        }

        return $formattedItems;
    }

    private function formatFromObject(object $item): OrderItemData
    {
        return new OrderItemData(
            itemName: $item->product->name ?? self::DEFAULT_ITEM_NAME,
            number: (int)($item->quantity ?? 1),
            itemValue: (string)($item->price_at_purchase ?? '0'),
            englishName: $this->getEnglishName($item),
            chineseName: '',
            itemType: self::DEFAULT_ITEM_TYPE,
            priceCurrency: self::CURRENCY,
            itemUrl: '',
            desc: $item->product->description ?? self::DEFAULT_DESCRIPTION
        );
    }

    private function formatFromArray(array $item): OrderItemData
    {
        return new OrderItemData(
            itemName: $item['product']['name'] ?? self::DEFAULT_ITEM_NAME,
            number: (int)($item['quantity'] ?? 1),
            itemValue: (string)($item['price_at_purchase'] ?? '0'),
            englishName: '',
            chineseName: '',
            itemType: self::DEFAULT_ITEM_TYPE,
            priceCurrency: self::CURRENCY,
            itemUrl: '',
            desc: $item['product']['description'] ?? self::DEFAULT_DESCRIPTION
        );
    }

    private function getDefaultItem(): OrderItemData
    {
        return new OrderItemData(
            itemName: self::DEFAULT_ITEM_NAME,
            number: 1,
            itemValue: '0',
            itemType: self::DEFAULT_ITEM_TYPE,
            priceCurrency: self::CURRENCY,
            desc: self::DEFAULT_DESCRIPTION
        );
    }

    private function getEnglishName(object $item): string
    {
        if (!isset($item->product)) {
            return '';
        }

        if (method_exists($item->product, 'getTranslation')) {
            return $item->product->getTranslation('name', 'en') ?? '';
        }

        return '';
    }
}