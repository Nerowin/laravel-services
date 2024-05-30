<?php

namespace Nerow\Services\Providers;

use Nerow\Services\ServiceManager;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->commands([
            \Nerow\Services\Console\Commands\MakeService::class
        ]);

        ServiceManager::makeServiceFolder()
        && ! ServiceManager::serviceFileExist('Service')
        && ($stub = ServiceManager::getStubFile('service.default'))
        && ServiceManager::makeFileService('Service', $stub);
    }
}