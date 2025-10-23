<?php

namespace GeniusCode\JTExpressEg\Tests;

use GeniusCode\JTExpressEg\JTExpressServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            JTExpressServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('jt-express.apiAccount', '292508153084379141');
        config()->set('jt-express.privateKey', 'test-private-key');
        config()->set('jt-express.customerCode', 'J0086000020');
        config()->set('jt-express.customerPwd', 'test-customer-pwd');
        config()->set('app.env', 'testing');
    }
}
