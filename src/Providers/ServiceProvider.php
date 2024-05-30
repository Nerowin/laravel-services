<?php

namespace Nerow\Services\Providers;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->commands([
            \Nerow\Services\Console\Commands\MakeService::class
        ]);
    }
}