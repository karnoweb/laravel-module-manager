<?php

namespace Karnoweb\LaravelModuleManager\Facades;

use Illuminate\Support\Facades\Facade;
use Karnoweb\LaravelModuleManager\Contracts\ModuleManagerInterface;

/**
 * @method static bool active(string|array $keys)
 * @method static bool inactive(string $key)
 * @method static bool allActive(array $keys)
 * @method static bool someActive(array $keys)
 * @method static mixed when(string $key, \Closure $active, ?\Closure $inactive = null)
 * @method static mixed unless(string $key, \Closure $callback)
 * @method static bool activate(string $key)
 * @method static bool deactivate(string $key)
 * @method static bool toggle(string $key)
 * @method static bool canActivate(string $key)
 * @method static bool canDeactivate(string $key)
 * @method static array whyCantActivate(string $key)
 * @method static array whyCantDeactivate(string $key)
 * @method static void requires(string $module, string $dependency)
 * @method static void conflicts(string $module, string $conflictsWith)
 * @method static void suggests(string $module, string $suggestion)
 * @method static \Illuminate\Support\Collection getDependencies(string $key)
 * @method static \Illuminate\Support\Collection getDependents(string $key)
 * @method static \Illuminate\Support\Collection tree(?string $group = null)
 * @method static \Illuminate\Support\Collection children(string $key)
 * @method static \Illuminate\Support\Collection descendants(string $key)
 * @method static \Illuminate\Support\Collection ancestors(string $key)
 * @method static \Illuminate\Support\Collection siblings(string $key)
 * @method static mixed meta(string $key, string $metaKey, mixed $default = null)
 * @method static void setMeta(string $key, string|array $metaKey, mixed $value = null)
 * @method static \Karnoweb\LaravelModuleManager\Models\Module define(string $key, string $name, array $options = [])
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection groups()
 * @method static \Illuminate\Support\Collection group(string $group)
 * @method static \Karnoweb\LaravelModuleManager\Models\Module|null find(string $key)
 * @method static \Karnoweb\LaravelModuleManager\Models\Module findOrFail(string $key)
 * @method static void flushCache()
 *
 * @see \Karnoweb\LaravelModuleManager\Services\ModuleManager
 */
class Module extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleManagerInterface::class;
    }
}
