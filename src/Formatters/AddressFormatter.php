<?php

namespace Appleera1\JtExpressEg\Formatters;

use Appleera1\JtExpressEg\DTOs\AddressData;

class AddressFormatter
{
    private const COUNTRY_CODE = 'EGY';
    private const DEFAULT_PHONE = '01000000000';
    private const DEFAULT_RECEIVER_NAME = 'Test Receiver';
    private const DEFAULT_RECEIVER_PROV = 'القاهرة';
    private const DEFAULT_RECEIVER_CITY = 'مدينة نصر';

    public function formatReceiver(mixed $shippingAddress): AddressData
    {
        if (empty($shippingAddress)) {
            return $this->getDefaultReceiverData();
        }

        $extractedData = is_object($shippingAddress)
            ? $this->extractFromObject($shippingAddress)
            : $this->extractFromArray($shippingAddress);

        return $this->buildAddressData($extractedData);
    }

    public function formatSender(): AddressData
    {
        return new AddressData(
            name: config('jt-express.sender.name', 'Test Sender'),
            mobile: config('jt-express.sender.mobile', self::DEFAULT_PHONE),
            phone: config('jt-express.sender.phone', self::DEFAULT_PHONE),
            countryCode: self::COUNTRY_CODE,
            prov: config('jt-express.sender.prov', 'الجيزة'),
            city: config('jt-express.sender.city', 'مدينة السادس من أكتوبر'),
            area: config('jt-express.sender.area', 'test area'),
            street: config('jt-express.sender.street', '456'),
            building: config('jt-express.sender.building', '1'),
            floor: config('jt-express.sender.floor', '22'),
            flats: config('jt-express.sender.flats', '33'),
            company: config('jt-express.sender.company', 'testCompany'),
            mailBox: config('jt-express.sender.mailBox', ''),
            postCode: config('jt-express.sender.postCode', ''),
            latitude: config('jt-express.sender.latitude', ''),
            longitude: config('jt-express.sender.longitude', '')
        );
    }

    private function getDefaultReceiverData(): AddressData
    {
        return new AddressData(
            name: self::DEFAULT_RECEIVER_NAME,
            mobile: self::DEFAULT_PHONE,
            phone: self::DEFAULT_PHONE,
            countryCode: self::COUNTRY_CODE,
            prov: self::DEFAULT_RECEIVER_PROV,
            city: self::DEFAULT_RECEIVER_CITY,
            area: 'test area',
            street: 'test street'
        );
    }

    private function extractFromObject(object $address): array
    {
        return [
            'name' => trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')),
            'mobile' => $address->phone ?? self::DEFAULT_PHONE,
            'phone' => $address->phone ?? self::DEFAULT_PHONE,
            'prov' => $address->state->name ?? $address->city->name ?? self::DEFAULT_RECEIVER_PROV,
            'city' => $address->city->name ?? self::DEFAULT_RECEIVER_CITY,
            'area' => $address->area ?? $address->state->name ?? '',
            'street' => $address->street ?? $address->address_line1 ?? '',
            'building' => $address->building ?? '',
            'floor' => $address->floor ?? '',
            'flats' => $address->flats ?? '',
            'company' => $address->company ?? '',
            'mailBox' => $address->user->email ?? '',
            'postCode' => $address->post_code ?? '',
            'latitude' => $address->latitude ?? '',
            'longitude' => $address->longitude ?? ''
        ];
    }

    private function extractFromArray(array $address): array
    {
        return [
            'name' => trim(($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? '')),
            'mobile' => $address['phone'] ?? self::DEFAULT_PHONE,
            'phone' => $address['phone'] ?? self::DEFAULT_PHONE,
            'prov' => $address['state']['name'] ?? $address['city']['name'] ?? self::DEFAULT_RECEIVER_PROV,
            'city' => $address['city']['name'] ?? self::DEFAULT_RECEIVER_CITY,
            'area' => $address['area'] ?? $address['state']['name'] ?? '',
            'street' => $address['street'] ?? $address['address_line1'] ?? '',
            'building' => $address['building'] ?? '',
            'floor' => $address['floor'] ?? '',
            'flats' => $address['flats'] ?? '',
            'company' => $address['company'] ?? '',
            'mailBox' => $address['user']['email'] ?? '',
            'postCode' => $address['post_code'] ?? '',
            'latitude' => $address['latitude'] ?? '',
            'longitude' => $address['longitude'] ?? ''
        ];
    }

    private function buildAddressData(array $data): AddressData
    {
        return new AddressData(
            name: $data['name'],
            mobile: $data['mobile'],
            phone: $data['phone'],
            countryCode: self::COUNTRY_CODE,
            prov: $data['prov'],
            city: $data['city'],
            area: $data['area'],
            street: $data['street'],
            building: $data['building'],
            floor: $data['floor'],
            flats: $data['flats'],
            company: $data['company'],
            mailBox: $data['mailBox'],
            postCode: $data['postCode'],
            latitude: $data['latitude'],
            longitude: $data['longitude']
        );
    }
}