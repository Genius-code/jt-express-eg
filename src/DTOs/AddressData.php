<?php

namespace Appleera1\JtExpressEg\DTOs;

class AddressData
{
    public function __construct(
        public readonly string $name,
        public readonly string $mobile,
        public readonly string $phone,
        public readonly string $countryCode,
        public readonly string $prov,
        public readonly string $city,
        public readonly string $area,
        public readonly string $street,
        public readonly string $building = '',
        public readonly string $floor = '',
        public readonly string $flats = '',
        public readonly string $company = '',
        public readonly string $mailBox = '',
        public readonly string $postCode = '',
        public readonly string $latitude = '',
        public readonly string $longitude = ''
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'countryCode' => $this->countryCode,
            'prov' => $this->prov,
            'city' => $this->city,
            'area' => $this->area,
            'street' => $this->street,
            'building' => $this->building,
            'floor' => $this->floor,
            'flats' => $this->flats,
            'company' => $this->company,
            'mailBox' => $this->mailBox,
            'postCode' => $this->postCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}