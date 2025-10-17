<?php

return [
    'apiAccount'   => env('JT_API_ACCOUNT', '292508153084379141'),
    'privateKey'   => env('JT_PRIVATE_KEY'),
    'customerCode' => env('JT_CUSTOMER_CODE', 'J0086000020'),
    'customerPwd'  => env('JT_CUSTOMER_PWD', '4AF43B0704D20349725BF0BBB64051BB'),

    'sender' => [
        'name'      => env('JT_SENDER_NAME', 'Test Sender'),
        'mobile'    => env('JT_SENDER_MOBILE', '01000000000'),
        'phone'     => env('JT_SENDER_PHONE', '01000000000'),
        'prov'      => env('JT_SENDER_PROV', 'الجيزة'),
        'city'      => env('JT_SENDER_CITY', 'مدينة السادس من أكتوبر'),
        'area'      => env('JT_SENDER_AREA', 'test area'),
        'street'    => env('JT_SENDER_STREET', '456'),
        'building'  => env('JT_SENDER_BUILDING', '1'),
        'floor'     => env('JT_SENDER_FLOOR', '22'),
        'flats'     => env('JT_SENDER_FLATS', '33'),
        'company'   => env('JT_SENDER_COMPANY', 'testCompany'),
        'mailBox'   => env('JT_SENDER_MAILBOX', ''),
        'postCode'  => env('JT_SENDER_POSTCODE', ''),
        'latitude'  => env('JT_SENDER_LAT', ''),
        'longitude' => env('JT_SENDER_LNG', ''),
    ],

    'digest' => env('JT_DIGEST', '')
];