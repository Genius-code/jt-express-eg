<?php

namespace Appleera1\JtExpressEg\Facades;

use Appleera1\JtExpressEg\JTExpressService;
use Illuminate\Support\Facades\Facade;

class JTExpress extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JTExpressService::class;
    }
}