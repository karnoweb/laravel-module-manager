<?php

namespace Karnoweb\LaravelModuleManager;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Karnoweb\LaravelModuleManager\Contracts\ModuleManagerInterface;
use Karnoweb\LaravelModuleManager\Http\Middleware\EnsureModuleIsActive;
use Karnoweb\LaravelModuleManager\Services\DependencyResolver;
use Karnoweb\LaravelModuleManager\Services\ModuleManager;
use Karnoweb\LaravelModuleManager\Services\TreeBuilder;

class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/module-manager.php',
            'module-manager'
        );

        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(TreeBuilder::class);

        $this->app->singleton(ModuleManagerInterface::class, function ($app) {
            return new ModuleManager(
                $app->make(DependencyResolver::class),
                $app->make(TreeBuilder::class)
            );
        });

        $this->app->alias(ModuleManagerInterface::class, 'module');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/module-manager.php' => config_path('module-manager.php'),
        ], 'module-manager-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'module-manager-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerBladeDirectives();
        $this->registerMiddleware();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ModuleListCommand::class,
                Console\ModuleActivateCommand::class,
                Console\ModuleDeactivateCommand::class,
                Console\ModuleTreeCommand::class,
                Console\ModuleSyncCommand::class,
            ]);
        }
    }

    protected function registerBladeDirectives(): void
    {
        Blade::if('module', function (string|array $keys) {
            return module_active($keys);
        });

        Blade::if('moduleany', function (array $keys) {
            return app(ModuleManagerInterface::class)->someActive($keys);
        });

        Blade::if('modules', function (array $keys) {
            return module_active($keys);
        });
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('module', EnsureModuleIsActive::class);
    }
}
