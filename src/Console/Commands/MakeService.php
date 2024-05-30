<?php

namespace Nerow\Services\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new migration generator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceName = $this->argument('service');
        $folderPath  = app_path('Services');
        $filePath    = "$folderPath/$serviceName.php";

        $this->files->exists($filePath)
        && $this->fail('Service already exists.');

        ! is_dir($folderPath)
        && mkdir($folderPath, 0777);

        $stubName    = $this->option('resources') ? 'service.resources' : 'service';
        $serviceStub = $this->files->get(__DIR__ . "/../../../stubs/$stubName.stub");
        $serviceStub = str_replace('{{ class }}', $serviceName, $serviceStub);
        $serviceStub = str_replace('{{ model }}', str_replace('Service', '', $serviceName), $serviceStub);

        $this->files->put($filePath, $serviceStub);
        $this->info('Service created successfully.');
    }
}
