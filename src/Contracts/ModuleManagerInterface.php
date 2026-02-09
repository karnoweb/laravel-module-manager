<?php

namespace Karnoweb\LaravelModuleManager\Contracts;

use Closure;
use Illuminate\Support\Collection;
use Karnoweb\LaravelModuleManager\Models\Module;

interface ModuleManagerInterface
{
    // Status checks
    public function active(string|array $keys): bool;

    public function inactive(string $key): bool;

    public function allActive(array $keys): bool;

    public function someActive(array $keys): bool;

    // Conditional execution
    public function when(string $key, Closure $active, ?Closure $inactive = null): mixed;

    public function unless(string $key, Closure $callback): mixed;

    // Activation/Deactivation
    public function activate(string $key): bool;

    public function deactivate(string $key): bool;

    public function toggle(string $key): bool;

    // Validation
    public function canActivate(string $key): bool;

    public function canDeactivate(string $key): bool;

    public function whyCantActivate(string $key): array;

    public function whyCantDeactivate(string $key): array;

    // Dependencies
    public function requires(string $module, string $dependency): void;

    public function conflicts(string $module, string $conflictsWith): void;

    public function suggests(string $module, string $suggestion): void;

    public function getDependencies(string $key): Collection;

    public function getDependents(string $key): Collection;

    // Tree operations
    public function tree(?string $group = null): Collection;

    public function children(string $key): Collection;

    public function descendants(string $key): Collection;

    public function ancestors(string $key): Collection;

    public function siblings(string $key): Collection;

    // Metadata
    public function meta(string $key, string $metaKey, mixed $default = null): mixed;

    public function setMeta(string $key, string|array $metaKey, mixed $value = null): void;

    // Management
    public function define(string $key, string $name, array $options = []): Module;

    public function all(): Collection;

    public function groups(): Collection;

    public function group(string $group): Collection;

    public function find(string $key): ?Module;

    public function findOrFail(string $key): Module;

    // Cache
    public function flushCache(): void;
}
