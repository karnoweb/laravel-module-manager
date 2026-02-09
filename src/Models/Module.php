<?php

namespace Karnoweb\LaravelModuleManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Karnoweb\LaravelModuleManager\Enums\DeactivationBehavior;
use Karnoweb\LaravelModuleManager\Enums\DependencyType;

class Module extends Model
{
    protected $fillable = [
        'parent_id',
        'key',
        'name',
        'description',
        'group',
        'icon',
        'sort_order',
        'is_active',
        'is_system',
        'metadata',
        'on_deactivate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array',
        'on_deactivate' => DeactivationBehavior::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = $this->getTableName();
    }

    protected function getTableName(): string
    {
        $prefix = config('module-manager.table_prefix', '');
        return $prefix . config('module-manager.tables.modules', 'modules');
    }

    // ==================== Relationships ====================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function dependencies(): BelongsToMany
    {
        $depTable = config('module-manager.table_prefix', '') . config('module-manager.tables.dependencies', 'module_dependencies');
        return $this->belongsToMany(
            self::class,
            $depTable,
            'module_id',
            'dependency_id'
        )->withPivot('type');
    }

    public function dependents(): BelongsToMany
    {
        $depTable = config('module-manager.table_prefix', '') . config('module-manager.tables.dependencies', 'module_dependencies');
        return $this->belongsToMany(
            self::class,
            $depTable,
            'dependency_id',
            'module_id'
        )->withPivot('type');
    }

    // ==================== Scopes ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeNonSystem(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    // ==================== Dependency Helpers ====================

    public function getRequirements(): Collection
    {
        return $this->dependencies()
            ->wherePivot('type', DependencyType::REQUIRES->value)
            ->get();
    }

    public function getConflicts(): Collection
    {
        return $this->dependencies()
            ->wherePivot('type', DependencyType::CONFLICTS->value)
            ->get();
    }

    public function getSuggestions(): Collection
    {
        return $this->dependencies()
            ->wherePivot('type', DependencyType::SUGGESTS->value)
            ->get();
    }

    public function getRequiredBy(): Collection
    {
        return $this->dependents()
            ->wherePivot('type', DependencyType::REQUIRES->value)
            ->get();
    }

    // ==================== Tree Helpers ====================

    public function getDescendants(): Collection
    {
        $descendants = collect();
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        return $descendants;
    }

    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        return $ancestors;
    }

    public function getSiblings(): Collection
    {
        return self::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->ordered()
            ->get();
    }

    public function getDepth(): int
    {
        return $this->getAncestors()->count();
    }

    public function getPath(): string
    {
        $path = $this->getAncestors()->reverse()->values()->pluck('key')->push($this->key);
        return $path->implode('.');
    }

    // ==================== Metadata Helpers ====================

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMeta(string $key, mixed $value): self
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
        return $this;
    }

    // ==================== State Helpers ====================

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasParent(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
