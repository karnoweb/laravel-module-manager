# Laravel Module Manager

A module/feature-flag system for Laravel with dependency resolution, tree structure, system (locked) modules, and Laravel 10–12 support.

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x or 12.x

## Installation

```bash
composer require karnoweb/laravel-module-manager
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=module-manager-config
php artisan vendor:publish --tag=module-manager-migrations
php artisan migrate
```

## Configuration

Edit `config/module-manager.php`:

| Key | Description |
|-----|-------------|
| `table_prefix` | Optional prefix for tables (e.g. `mm_` → `mm_modules`) |
| `tables.modules` | Modules table name |
| `tables.dependencies` | Dependencies table name |
| `migration_prefix` | Prefix for published migration filenames |
| `cache.enabled` | Enable cache for active status |
| `cache.ttl` | Cache TTL (seconds) |
| `cache.prefix` | Cache key prefix |
| `default_deactivation` | `cascade`, `restrict`, or `none` |
| `events.enabled` | Fire module events |
| `modules` | Array of module definitions (see below) |

### Defining modules in config

Add modules under `modules` so they can be synced with `php artisan module:sync`:

```php
'modules' => [
    'products' => [
        'name' => 'Products',
        'description' => 'Product management',
        'group' => 'shop',
        'icon' => 'fa-box',
        'sort_order' => 0,
        'is_active' => false,
        'is_system' => false,
        'on_deactivate' => 'restrict',
        'metadata' => [],
        'parent' => null,
        'requires' => [],
        'conflicts' => [],
        'suggests' => [],
    ],
    'simple_product' => [
        'name' => 'Simple Product',
        'group' => 'shop',
        'parent' => 'products',
        'is_active' => true,
        'requires' => ['products'],
    ],
],
```

Sync from config:

```bash
php artisan module:sync
```

---

## API Reference

### Status checks

| Method | Description |
|--------|-------------|
| `Module::active(string\|array $keys)` | Returns `true` if module(s) are active |
| `Module::inactive(string $key)` | Returns `true` if module is inactive |
| `Module::allActive(array $keys)` | All given keys must be active |
| `Module::someActive(array $keys)` | At least one key active |

### Conditional execution

| Method | Description |
|--------|-------------|
| `Module::when(string $key, Closure $active, ?Closure $inactive = null)` | Run callback by status |
| `Module::unless(string $key, Closure $callback)` | Run callback when inactive |

### Activation / deactivation

| Method | Description |
|--------|-------------|
| `Module::activate(string $key)` | Activate module (throws on missing deps / conflicts) |
| `Module::deactivate(string $key)` | Deactivate (throws for system modules or active dependents) |
| `Module::toggle(string $key)` | Toggle active state; returns new state |

### Validation

| Method | Description |
|--------|-------------|
| `Module::canActivate(string $key)` | Whether activation is allowed |
| `Module::canDeactivate(string $key)` | Whether deactivation is allowed |
| `Module::whyCantActivate(string $key)` | Reasons (e.g. `missing_dependencies`, `conflicts`) |
| `Module::whyCantDeactivate(string $key)` | Reasons (e.g. `system_module`, `active_dependents`) |

### Dependencies

| Method | Description |
|--------|-------------|
| `Module::requires(string $module, string $dependency)` | Add required dependency |
| `Module::conflicts(string $module, string $conflictsWith)` | Add conflict (bidirectional) |
| `Module::suggests(string $module, string $suggestion)` | Add suggestion |
| `Module::getDependencies(string $key)` | Get required modules |
| `Module::getDependents(string $key)` | Get modules that require this one |

### Tree

| Method | Description |
|--------|-------------|
| `Module::tree(?string $group = null)` | Nested tree (with `children`) |
| `Module::children(string $key)` | Direct children |
| `Module::descendants(string $key)` | All descendants |
| `Module::ancestors(string $key)` | All ancestors |
| `Module::siblings(string $key)` | Siblings |

### Metadata

| Method | Description |
|--------|-------------|
| `Module::meta(string $key, string $metaKey, mixed $default = null)` | Get metadata value |
| `Module::setMeta(string $key, string\|array $metaKey, mixed $value = null)` | Set metadata |

### Management

| Method | Description |
|--------|-------------|
| `Module::define(string $key, string $name, array $options = [])` | Create or update module |
| `Module::all()` | All modules (ordered) |
| `Module::groups()` | List of groups |
| `Module::group(string $group)` | Modules in group |
| `Module::find(string $key)` | Find by key or null |
| `Module::findOrFail(string $key)` | Find or throw |
| `Module::flushCache()` | Clear active-status cache |

### Helpers

| Function | Description |
|---------|-------------|
| `module(?string $key = null)` | Manager instance or `active($key)` |
| `module_active(string\|array $keys)` | Same as `Module::active()` |
| `module_inactive(string $key)` | Same as `Module::inactive()` |
| `module_meta(string $key, string $metaKey, mixed $default = null)` | Same as `Module::meta()` |
| `when_module(string $key, Closure $active, ?Closure $inactive = null)` | Same as `Module::when()` |

---

## Artisan commands

| Command | Description |
|--------|-------------|
| `php artisan module:list` | List modules (`--group=`, `--active`, `--inactive`) |
| `php artisan module:activate {key}` | Activate a module |
| `php artisan module:deactivate {key}` | Deactivate a module |
| `php artisan module:tree` | Show tree (`--group=`, `--json`) |
| `php artisan module:sync` | Sync modules from config (`--force` to skip confirm) |

---

## Usage examples

### Check and run code by module

```php
use Karnoweb\LaravelModuleManager\Facades\Module;

if (Module::active('reports')) {
    return view('reports.dashboard');
}

Module::when('advanced_discount', function () {
    return redirect()->route('discounts.advanced');
}, function () {
    return redirect()->route('discounts.simple');
});
```

### Blade

```blade
@module('reports')
    <a href="{{ route('reports.index') }}">Reports</a>
@endmodule

@moduleany(['simple_product', 'coding_product'])
    <a href="{{ route('products.index') }}">Products</a>
@endmoduleany

@modules(['products', 'discounts'])
    <p>Shop modules are active.</p>
@endmodules
```

### Routes and middleware

```php
Route::middleware(['module:reports'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});

Route::middleware(['module:products,discounts'])->get('/shop', ...);
```

### Define modules and dependencies in code

```php
use Karnoweb\LaravelModuleManager\Facades\Module;

Module::define('products', 'Products', [
    'group' => 'shop',
    'icon' => 'fa-box',
    'is_active' => true,
]);

Module::define('simple_product', 'Simple Product', [
    'group' => 'shop',
    'parent' => 'products',
    'is_active' => true,
]);

Module::requires('simple_product', 'products');
Module::conflicts('legacy_cart', 'new_cart');
Module::suggests('reports', 'products');
```

### System (locked) modules

Set `is_system => true` in config or when defining. System modules cannot be deactivated via `Module::deactivate()` or `module:deactivate` (throws `SystemModuleException`).

```php
Module::define('core', 'Core', ['is_system' => true]);
```

### Sync from config in app seeder

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Karnoweb\LaravelModuleManager\ModuleSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        (new ModuleSeeder)->run();
    }
}
```

---

## Exceptions

| Exception | When |
|-----------|------|
| `ModuleNotFoundException` | Module key not found |
| `DependencyException` | Missing deps or active dependents block action |
| `ConflictException` | Conflicting module is active |
| `CircularDependencyException` | Circular requires detected |
| `SystemModuleException` | Deactivate attempted on system module |

---

## License

MIT.
