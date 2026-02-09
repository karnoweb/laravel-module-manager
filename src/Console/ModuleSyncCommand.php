<?php

namespace Karnoweb\LaravelModuleManager\Console;

use Illuminate\Console\Command;
use Karnoweb\LaravelModuleManager\ModuleSeeder;

class ModuleSyncCommand extends Command
{
    protected $signature = 'module:sync
                            {--force : Run without confirmation}';

    protected $description = 'Sync modules from config (create new, update existing)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Sync modules from config/module-manager.php?')) {
            return self::SUCCESS;
        }

        $seeder = new ModuleSeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Modules synced from config.');
        return self::SUCCESS;
    }
}
