<?php

namespace Nerow\Services\Providers;

use Nerow\Services\ServiceManager;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Nerow\Services\Console\Commands\MakeService;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        $this->commands(MakeService::class);

        ServiceManager::makeServiceFolder()
        && ! ServiceManager::serviceFileExist('Service')
        && ($stub = ServiceManager::getStubFile('service.default'))
        && ServiceManager::makeFileService('Service', $stub);
    }
}