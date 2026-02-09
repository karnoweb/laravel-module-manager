<?php

namespace Karnoweb\LaravelModuleManager\Console;

use Illuminate\Console\Command;
use Karnoweb\LaravelModuleManager\Facades\Module;

class ModuleListCommand extends Command
{
    protected $signature = 'module:list
                            {--group= : Filter by group}
                            {--active : Only active modules}
                            {--inactive : Only inactive modules}';

    protected $description = 'List all modules';

    public function handle(): int
    {
        $query = Module::all();

        if ($group = $this->option('group')) {
            $query = Module::group($group);
        }

        if ($this->option('active')) {
            $query = $query->filter(fn ($m) => $m->is_active);
        } elseif ($this->option('inactive')) {
            $query = $query->filter(fn ($m) => ! $m->is_active);
        }

        $rows = $query->map(fn ($m) => [
            $m->key,
            $m->name,
            $m->group,
            $m->is_active ? 'Yes' : 'No',
            $m->is_system ? 'Yes' : 'No',
        ])->toArray();

        $this->table(['Key', 'Name', 'Group', 'Active', 'System'], $rows);

        return self::SUCCESS;
    }
}
