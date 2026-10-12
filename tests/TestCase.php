<?php

namespace Irnoti\SmsPanelApi\Tests;

use Irnoti\SmsPanelApi\SmsPanelApiServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SmsPanelApiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sms-panel-api.base_url', 'https://irnoti.test/api');
        $app['config']->set('sms-panel-api.key', 'irn_test_key');
    }
}
