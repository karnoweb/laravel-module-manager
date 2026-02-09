<?php

namespace Karnoweb\LaravelModuleManager;

use Illuminate\Console\Command;
use Karnoweb\LaravelModuleManager\Facades\Module;

class ModuleSeeder
{
    protected ?Command $command = null;

    public function setCommand(?Command $command): self
    {
        $this->command = $command;
        return $this;
    }

    public function run(): void
    {
        $raw = config('module-manager.modules', []);

        if (empty($raw)) {
            if ($this->command) {
                $this->command->warn('No modules defined in config/module-manager.php [modules].');
            }
            return;
        }

        $modules = $this->flattenModules($raw);

        $ordered = $this->orderByParent(array_keys($modules), $modules);

        foreach ($ordered as $key) {
            $options = $modules[$key];
            $name = $options['name'] ?? $key;
            $opts = [
                'description' => $options['description'] ?? null,
                'group' => $options['group'] ?? 'general',
                'icon' => $options['icon'] ?? null,
                'sort_order' => (int) ($options['sort_order'] ?? 0),
                'is_active' => (bool) ($options['is_active'] ?? false),
                'is_system' => (bool) ($options['is_system'] ?? false),
                'on_deactivate' => $options['on_deactivate'] ?? config('module-manager.default_deactivation', 'restrict'),
                'metadata' => $options['metadata'] ?? null,
            ];
            if (isset($options['parent']) && $options['parent'] !== null) {
                $opts['parent'] = $options['parent'];
            }
            Module::define($key, $name, $opts);
            if ($this->command && $this->command->getOutput()->isVerbose()) {
                $this->command->line("  Synced module: {$key}");
            }
        }

        foreach ($modules as $key => $options) {
            foreach ($options['requires'] ?? [] as $dep) {
                try {
                    Module::requires($key, $dep);
                } catch (\Throwable $e) {
                    if ($this->command) {
                        $this->command->warn("  Skip requires {$key} -> {$dep}: " . $e->getMessage());
                    }
                }
            }
            foreach ($options['conflicts'] ?? [] as $conflict) {
                try {
                    Module::conflicts($key, $conflict);
                } catch (\Throwable $e) {
                    if ($this->command) {
                        $this->command->warn("  Skip conflicts {$key} -> {$conflict}: " . $e->getMessage());
                    }
                }
            }
            foreach ($options['suggests'] ?? [] as $suggestion) {
                try {
                    Module::suggests($key, $suggestion);
                } catch (\Throwable $e) {
                    if ($this->command) {
                        $this->command->warn("  Skip suggests {$key} -> {$suggestion}: " . $e->getMessage());
                    }
                }
            }
        }

        Module::flushCache();
    }

    /**
     * Order module keys so parents come before children.
     *
     * @param  array<string>  $keys
     * @param  array<string, array>  $modules
     * @return array<string>
     */
    protected function orderByParent(array $keys, array $modules): array
    {
        $keySet = array_flip($keys);
        $order = [];
        $added = [];

        while (count($order) < count($keys)) {
            $progress = false;
            foreach ($keys as $key) {
                if (isset($added[$key])) {
                    continue;
                }
                $parent = $modules[$key]['parent'] ?? null;
                if ($parent === null || $parent === '') {
                    $order[] = $key;
                    $added[$key] = true;
                    $progress = true;
                    continue;
                }
                if (isset($keySet[$parent]) && ! isset($added[$parent])) {
                    continue;
                }
                $order[] = $key;
                $added[$key] = true;
                $progress = true;
            }
            if (! $progress) {
                break;
            }
        }

        return $order;
    }

    /**
     * Flatten nested module config (with "records" key) into a flat key => options array.
     * Children inherit "group" and get "parent" set to the parent key.
     *
     * @param  array<string, mixed>  $nodes  Nested modules (each may have 'records' => [...])
     * @param  string|null  $parentKey  Parent module key when recursing
     * @param  array<string, mixed>  $parentOptions  Parent options for inheriting group
     * @return array<string, array>
     */
    protected function flattenModules(array $nodes, ?string $parentKey = null, array $parentOptions = []): array
    {
        $flat = [];

        foreach ($nodes as $key => $options) {
            if (! is_array($options)) {
                continue;
            }

            $records = $options['records'] ?? null;
            if (isset($records) && is_array($records)) {
                unset($options['records']);
            }

            $options['group'] = $options['group'] ?? $parentOptions['group'] ?? 'general';
            if ($parentKey !== null) {
                $options['parent'] = $parentKey;
            }

            $flat[$key] = $options;

            if (isset($records) && is_array($records)) {
                $flat = array_merge($flat, $this->flattenModules($records, $key, $options));
            }
        }

        return $flat;
    }
}
