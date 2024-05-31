<?php

namespace Nerow\Services\Providers;

use Nerow\Services\Helpers\ServiceHelper;
use Nerow\Services\Console\Commands\MakeService;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        $this->commands(MakeService::class);

        ServiceHelper::makeServiceFolder()
        && ! ServiceHelper::serviceFileExist('Service')
        && ($stub = ServiceHelper::getStubFile('service.default'))
        && ServiceHelper::makeFileService('Service', $stub);
    }
}