<?php

namespace WenGen;

use Illuminate\Support\ServiceProvider;
use WenGen\Commands\ModelGen;

/**
 * @date: 18:45 2023/4/7
 */
class GenServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([ModelGen::class]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
