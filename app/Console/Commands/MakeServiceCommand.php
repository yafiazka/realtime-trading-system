<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name : The name of the service class}';
    protected $description = 'Create a new service class for business logic';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        if (!Str::endsWith($name, 'Service')) {
            $name .= 'Service';
        }

        $path = app_path("Services/{$name}.php");

        if ($this->files->exists($path)) {
            $this->error("Service {$name} already exists!");
            return Command::FAILURE;
        }

        $this->makeDirectory($path);

        $stub = $this->getStub();
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            ['App\Services', $name],
            $stub
        );

        $this->files->put($path, $stub);

        $this->info("Service {$name} created successfully.");

        return Command::SUCCESS;
    }

    protected function getStub(): string
    {
        return $this->files->get(base_path('app/Console/stubs/service.stub'));
    }

    protected function makeDirectory(string $path): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
}
