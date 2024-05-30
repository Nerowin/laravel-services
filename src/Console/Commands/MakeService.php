<?php

namespace Nerow\Services\Console\Commands;

use Illuminate\Console\Command;
use Nerow\Services\ServiceManager;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service 
                            {service : service name} 
                            {--resources : generate default resources methods}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Making a new service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceName = $this->argument('service');
        $stubName    = $this->option('resources') ? 'service.resources' : 'service';

        ServiceManager::serviceFileExist($serviceName)
        && $this->fail('Service already exists.');

        ServiceManager::makeServiceFolder()
        && $this->fail('Unable to create services folder.');

        $serviceStub = ServiceManager::getStubFile($stubName);
        $serviceStub = str_replace('{{ class }}', $serviceName, $serviceStub);
        $serviceStub = str_replace('{{ model }}', str_replace('Service', '', $serviceName), $serviceStub);
        $serviceStub = str_replace(
            '{{ service }}',
            ServiceManager::serviceFileExist('Service') ? 'Service' : 'Nerow\Service',
            $serviceStub
        );

        ServiceManager::makeFileService($serviceName, $serviceStub)
        && $this->info('Service created successfully.')
        || $this->fail('Error encounter while attempting to create service file.');
    }
}
