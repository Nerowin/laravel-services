<?php

namespace Nerow\Services\Console\Commands;

use Illuminate\Console\Command;
use Nerow\Tools\ServiceHelper;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service 
                            {service : Service name} 
                            {--r|resources : Generate default resources methods}';

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
        [$name, $resource] = $this->getComputedOptions();

        if (ServiceHelper::serviceFileExist($name)) {
            $this->fail('Service already exists.');
        }

        ServiceHelper::makeServiceFolder();

        $stub = ServiceHelper::getStubFile($resource ? 'service.resources' : 'service');
        $stub = str_replace('{{ class }}', $name, $stub);
        $stub = str_replace('{{ model }}', str_replace('Service', '', $name), $stub);

        ServiceHelper::makeServiceFile($name, $stub);
        
        $this->info('Service created successfully.');
    }
}
