<?php

namespace Ngoni\DischubPhp\Tests;

use Ngoni\DischubPhp\DischubServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DischubServiceProvider::class,
        ];
    }
}
