<?php

namespace Karnoweb\LaravelModuleManager\Console;

use Illuminate\Console\Command;
use Karnoweb\LaravelModuleManager\Exceptions\ConflictException;
use Karnoweb\LaravelModuleManager\Exceptions\DependencyException;
use Karnoweb\LaravelModuleManager\Exceptions\ModuleNotFoundException;
use Karnoweb\LaravelModuleManager\Facades\Module;

class ModuleActivateCommand extends Command
{
    protected $signature = 'module:activate {key : Module key}';

    protected $description = 'Activate a module';

    public function handle(): int
    {
        $key = $this->argument('key');

        try {
            Module::activate($key);
            $this->info("Module '{$key}' activated.");
            return self::SUCCESS;
        } catch (ModuleNotFoundException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (DependencyException $e) {
            $this->error($e->getMessage());
            $this->error('Missing: ' . implode(', ', $e->getDependencies()));
            return self::FAILURE;
        } catch (ConflictException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
