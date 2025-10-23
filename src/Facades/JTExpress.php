<?php

namespace GeniusCode\JTExpressEg\Facades;

use GeniusCode\JTExpressEg\JTExpressService;
use Illuminate\Support\Facades\Facade;

class JTExpress extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JTExpressService::class;
    }
}