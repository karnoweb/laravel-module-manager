<?php

namespace Karnoweb\LaravelModuleManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\LaravelModuleManager\Enums\DependencyType;

class ModuleDependency extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'dependency_id',
        'type',
    ];

    protected $casts = [
        'type' => DependencyType::class,
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('module-manager.table_prefix', '') . config('module-manager.tables.dependencies', 'module_dependencies');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'dependency_id');
    }
}
