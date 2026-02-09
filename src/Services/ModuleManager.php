<?php

namespace Karnoweb\LaravelModuleManager\Services;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Karnoweb\LaravelModuleManager\Contracts\ModuleManagerInterface;
use Karnoweb\LaravelModuleManager\Enums\DeactivationBehavior;
use Karnoweb\LaravelModuleManager\Enums\DependencyType;
use Karnoweb\LaravelModuleManager\Events\ModuleActivated;
use Karnoweb\LaravelModuleManager\Events\ModuleActivating;
use Karnoweb\LaravelModuleManager\Events\ModuleDeactivated;
use Karnoweb\LaravelModuleManager\Events\ModuleDeactivating;
use Karnoweb\LaravelModuleManager\Exceptions\ConflictException;
use Karnoweb\LaravelModuleManager\Exceptions\DependencyException;
use Karnoweb\LaravelModuleManager\Exceptions\ModuleNotFoundException;
use Karnoweb\LaravelModuleManager\Exceptions\SystemModuleException;
use Karnoweb\LaravelModuleManager\Models\Module;
use Karnoweb\LaravelModuleManager\Models\ModuleDependency;

class ModuleManager implements ModuleManagerInterface
{
    protected DependencyResolver $resolver;

    protected TreeBuilder $treeBuilder;

    protected ?Collection $cachedModules = null;

    public function __construct(DependencyResolver $resolver, TreeBuilder $treeBuilder)
    {
        $this->resolver = $resolver;
        $this->treeBuilder = $treeBuilder;
    }

    // ==================== Status Checks ====================

    public function active(string|array $keys): bool
    {
        if (is_array($keys)) {
            return $this->allActive($keys);
        }
        return $this->getActiveStatus()->get($keys, false);
    }

    public function inactive(string $key): bool
    {
        return ! $this->active($key);
    }

    public function allActive(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->inactive($key)) {
                return false;
            }
        }
        return true;
    }

    public function someActive(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->active($key)) {
                return true;
            }
        }
        return false;
    }

    // ==================== Conditional Execution ====================

    public function when(string $key, Closure $active, ?Closure $inactive = null): mixed
    {
        if ($this->active($key)) {
            return $active();
        }
        return $inactive ? $inactive() : null;
    }

    public function unless(string $key, Closure $callback): mixed
    {
        if ($this->inactive($key)) {
            return $callback();
        }
        return null;
    }

    // ==================== Activation/Deactivation ====================

    public function activate(string $key): bool
    {
        $module = $this->findOrFail($key);

        if ($module->is_active) {
            return true;
        }

        $issues = $this->resolver->canActivate($module);

        if (! empty($issues['missing_dependencies'])) {
            throw new DependencyException(
                "Cannot activate '{$key}': missing dependencies",
                $issues['missing_dependencies']
            );
        }

        if (! empty($issues['conflicts'])) {
            throw new ConflictException($key, $issues['conflicts']);
        }

        if (config('module-manager.events.enabled')) {
            event(new ModuleActivating($module));
        }

        $module->is_active = true;
        $module->save();
        $this->flushCache();

        if (config('module-manager.events.enabled')) {
            event(new ModuleActivated($module));
        }

        return true;
    }

    public function deactivate(string $key): bool
    {
        $module = $this->findOrFail($key);

        if (! $module->is_active) {
            return true;
        }

        if ($module->is_system ?? false) {
            throw new SystemModuleException($key);
        }

        $issues = $this->resolver->canDeactivate($module);

        if (! empty($issues['system_module'])) {
            throw new SystemModuleException($key);
        }

        if (! empty($issues['active_dependents'])) {
            throw new DependencyException(
                "Cannot deactivate '{$key}': other modules depend on it",
                $issues['active_dependents']
            );
        }

        $cascadeModules = $this->resolver->getCascadeDeactivations($module);

        DB::transaction(function () use ($module, $cascadeModules) {
            foreach ($cascadeModules as $cascadeModule) {
                if (config('module-manager.events.enabled')) {
                    event(new ModuleDeactivating($cascadeModule));
                }
                $cascadeModule->is_active = false;
                $cascadeModule->save();
                if (config('module-manager.events.enabled')) {
                    event(new ModuleDeactivated($cascadeModule));
                }
            }

            if (config('module-manager.events.enabled')) {
                event(new ModuleDeactivating($module));
            }
            $module->is_active = false;
            $module->save();
            if (config('module-manager.events.enabled')) {
                event(new ModuleDeactivated($module));
            }
        });

        $this->flushCache();
        return true;
    }

    public function toggle(string $key): bool
    {
        $module = $this->findOrFail($key);
        if ($module->is_active) {
            $this->deactivate($key);
            return false;
        }
        $this->activate($key);
        return true;
    }

    // ==================== Validation ====================

    public function canActivate(string $key): bool
    {
        $module = $this->find($key);
        if (! $module) {
            return false;
        }
        return empty($this->resolver->canActivate($module));
    }

    public function canDeactivate(string $key): bool
    {
        $module = $this->find($key);
        if (! $module) {
            return false;
        }
        return empty($this->resolver->canDeactivate($module));
    }

    public function whyCantActivate(string $key): array
    {
        $module = $this->find($key);
        if (! $module) {
            return ['not_found' => true];
        }
        return $this->resolver->canActivate($module);
    }

    public function whyCantDeactivate(string $key): array
    {
        $module = $this->find($key);
        if (! $module) {
            return ['not_found' => true];
        }
        return $this->resolver->canDeactivate($module);
    }

    // ==================== Dependencies ====================

    public function requires(string $module, string $dependency): void
    {
        $this->addDependency($module, $dependency, DependencyType::REQUIRES);
    }

    public function conflicts(string $module, string $conflictsWith): void
    {
        $this->addDependency($module, $conflictsWith, DependencyType::CONFLICTS);
        $this->addDependency($conflictsWith, $module, DependencyType::CONFLICTS);
    }

    public function suggests(string $module, string $suggestion): void
    {
        $this->addDependency($module, $suggestion, DependencyType::SUGGESTS);
    }

    protected function addDependency(string $moduleKey, string $depKey, DependencyType $type): void
    {
        $module = $this->findOrFail($moduleKey);
        $dependency = $this->findOrFail($depKey);

        if ($type === DependencyType::REQUIRES) {
            $this->resolver->validateNoCircularDependency($module, $dependency);
        }

        ModuleDependency::updateOrCreate(
            [
                'module_id' => $module->id,
                'dependency_id' => $dependency->id,
                'type' => $type->value,
            ]
        );

        $this->flushCache();
    }

    public function getDependencies(string $key): Collection
    {
        return $this->findOrFail($key)->getRequirements();
    }

    public function getDependents(string $key): Collection
    {
        return $this->findOrFail($key)->getRequiredBy();
    }

    // ==================== Tree Operations ====================

    public function tree(?string $group = null): Collection
    {
        return $this->treeBuilder->buildTree($group);
    }

    public function children(string $key): Collection
    {
        return $this->findOrFail($key)->children;
    }

    public function descendants(string $key): Collection
    {
        return $this->findOrFail($key)->getDescendants();
    }

    public function ancestors(string $key): Collection
    {
        return $this->findOrFail($key)->getAncestors();
    }

    public function siblings(string $key): Collection
    {
        return $this->findOrFail($key)->getSiblings();
    }

    // ==================== Metadata ====================

    public function meta(string $key, string $metaKey, mixed $default = null): mixed
    {
        $module = $this->find($key);
        return $module?->getMeta($metaKey, $default) ?? $default;
    }

    public function setMeta(string $key, string|array $metaKey, mixed $value = null): void
    {
        $module = $this->findOrFail($key);
        if (is_array($metaKey)) {
            $module->metadata = array_merge($module->metadata ?? [], $metaKey);
        } else {
            $module->setMeta($metaKey, $value);
        }
        $module->save();
        $this->flushCache();
    }

    // ==================== Management ====================

    public function define(string $key, string $name, array $options = []): Module
    {
        $parentId = null;
        if (isset($options['parent'])) {
            $parent = $this->findOrFail($options['parent']);
            $parentId = $parent->id;
        }

        $module = Module::updateOrCreate(
            ['key' => $key],
            [
                'parent_id' => $parentId,
                'name' => $name,
                'description' => $options['description'] ?? null,
                'group' => $options['group'] ?? 'general',
                'icon' => $options['icon'] ?? null,
                'sort_order' => $options['sort_order'] ?? 0,
                'is_active' => $options['is_active'] ?? false,
                'is_system' => $options['is_system'] ?? false,
                'metadata' => $options['metadata'] ?? null,
                'on_deactivate' => $options['on_deactivate'] ?? config('module-manager.default_deactivation', 'restrict'),
            ]
        );

        $this->flushCache();
        return $module;
    }

    public function all(): Collection
    {
        return Module::ordered()->get();
    }

    public function groups(): Collection
    {
        return $this->treeBuilder->getGroups();
    }

    public function group(string $group): Collection
    {
        return Module::inGroup($group)->ordered()->get();
    }

    public function find(string $key): ?Module
    {
        return Module::byKey($key)->first();
    }

    public function findOrFail(string $key): Module
    {
        $module = $this->find($key);
        if (! $module) {
            throw new ModuleNotFoundException($key);
        }
        return $module;
    }

    // ==================== Cache ====================

    protected function getActiveStatus(): Collection
    {
        if (! config('module-manager.cache.enabled')) {
            return Module::pluck('is_active', 'key');
        }
        $cacheKey = config('module-manager.cache.prefix') . 'active_status';
        $ttl = config('module-manager.cache.ttl', 3600);
        return Cache::remember($cacheKey, $ttl, fn () => Module::pluck('is_active', 'key'));
    }

    public function flushCache(): void
    {
        $this->cachedModules = null;
        if (config('module-manager.cache.enabled')) {
            Cache::forget(config('module-manager.cache.prefix') . 'active_status');
        }
    }
}
