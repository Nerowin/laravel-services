<?php

namespace Nerow\Services\Providers;

use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Nerow\Services\Console\Commands\MakeService;
use Nerow\Tools\ServiceHelper;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        $this->commands(MakeService::class);

        ServiceHelper::makeServiceFolder()
        && ! ServiceHelper::serviceFileExist('Service')
        && ($stub = ServiceHelper::getStubFile('service.default'))
        && ServiceHelper::makeServiceFile('Service', $stub);
    }
}