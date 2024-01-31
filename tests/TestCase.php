<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use WenGen\GenServiceProvider;

/**
 * @date: 15:34 2024/1/29
 */
class TestCase extends Orchestra
{
    /**
     * 获取设置的服务提供者
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            GenServiceProvider::class,
        ];
    }
}
