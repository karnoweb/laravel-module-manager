<?php

use Karnoweb\LaravelModuleManager\Contracts\ModuleManagerInterface;

if (! function_exists('module')) {
    /**
     * Get module manager instance or check if module is active.
     */
    function module(?string $key = null): ModuleManagerInterface|bool
    {
        $manager = app(ModuleManagerInterface::class);

        if (is_null($key)) {
            return $manager;
        }

        return $manager->active($key);
    }
}

if (! function_exists('module_active')) {
    /**
     * Check if module(s) are active.
     */
    function module_active(string|array $keys): bool
    {
        return app(ModuleManagerInterface::class)->active($keys);
    }
}

if (! function_exists('module_inactive')) {
    /**
     * Check if module is inactive.
     */
    function module_inactive(string $key): bool
    {
        return app(ModuleManagerInterface::class)->inactive($key);
    }
}

if (! function_exists('module_meta')) {
    /**
     * Get module metadata value.
     */
    function module_meta(string $key, string $metaKey, mixed $default = null): mixed
    {
        return app(ModuleManagerInterface::class)->meta($key, $metaKey, $default);
    }
}

if (! function_exists('when_module')) {
    /**
     * Execute callback when module is active.
     */
    function when_module(string $key, Closure $active, ?Closure $inactive = null): mixed
    {
        return app(ModuleManagerInterface::class)->when($key, $active, $inactive);
    }
}
