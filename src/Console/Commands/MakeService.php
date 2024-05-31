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
        $name = $this->argument('service');

        if (ServiceManager::serviceFileExist($name)) {
            $this->fail('Service already exists.');
        }

        if (! ServiceManager::makeServiceFolder()) {
            $this->fail('Unable to create services folder.');
        }

        $stub = ServiceManager::getStubFile(
            $this->option('resources') ? 'service.resources' : 'service'
        );
        $stub = str_replace('{{ class }}', $name, $stub);
        $stub = str_replace('{{ model }}', str_replace('Service', '', $name), $stub);
        $stub = str_replace(
            '{{ service }}',
            ServiceManager::serviceFileExist('Service') ? 'Service' : 'Nerow\\Services\\Service',
            $stub
        );

        if (ServiceManager::makeFileService($name, $stub)) {
            $this->info('Service created successfully.');
        } else {
            $this->fail('Error encounter while attempting to create service file.');
        }
    }
}
