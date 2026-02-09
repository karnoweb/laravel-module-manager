<?php

namespace Karnoweb\LaravelModuleManager\Console;

use Illuminate\Console\Command;
use Karnoweb\LaravelModuleManager\Facades\Module;

class ModuleTreeCommand extends Command
{
    protected $signature = 'module:tree
                            {--group= : Filter by group}
                            {--json : Output as JSON}';

    protected $description = 'Display module tree';

    public function handle(): int
    {
        $group = $this->option('group');
        $tree = Module::tree($group);

        if ($this->option('json')) {
            $this->line($tree->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->renderTree($tree->toArray(), 0);
        return self::SUCCESS;
    }

    protected function renderTree(array $nodes, int $depth): void
    {
        foreach ($nodes as $node) {
            $prefix = str_repeat('  ', $depth);
            $active = $node['is_active'] ? '<info>[active]</info>' : '';
            $system = ! empty($node['is_system']) ? ' <comment>(system)</comment>' : '';
            $this->line("{$prefix}- {$node['key']} — {$node['name']} {$active}{$system}");

            if (! empty($node['children'])) {
                $this->renderTree($node['children'], $depth + 1);
            }
        }
    }
}
