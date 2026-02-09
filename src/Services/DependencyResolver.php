<?php

namespace Karnoweb\LaravelModuleManager\Services;

use Karnoweb\LaravelModuleManager\Enums\DeactivationBehavior;
use Karnoweb\LaravelModuleManager\Enums\DependencyType;
use Karnoweb\LaravelModuleManager\Exceptions\CircularDependencyException;
use Karnoweb\LaravelModuleManager\Models\Module;
use Illuminate\Support\Collection;

class DependencyResolver
{
    public function canActivate(Module $module): array
    {
        $issues = [];

        $missingDeps = $this->getMissingDependencies($module);
        if ($missingDeps->isNotEmpty()) {
            $issues['missing_dependencies'] = $missingDeps->pluck('key')->toArray();
        }

        $conflicts = $this->getActiveConflicts($module);
        if ($conflicts->isNotEmpty()) {
            $issues['conflicts'] = $conflicts->pluck('key')->toArray();
        }

        return $issues;
    }

    public function canDeactivate(Module $module): array
    {
        $issues = [];

        if ($module->is_system ?? false) {
            $issues['system_module'] = true;
            return $issues;
        }

        if ($module->on_deactivate === DeactivationBehavior::RESTRICT) {
            $activeDependents = $this->getActiveDependents($module);
            if ($activeDependents->isNotEmpty()) {
                $issues['active_dependents'] = $activeDependents->pluck('key')->toArray();
            }
        }

        return $issues;
    }

    public function getCascadeDeactivations(Module $module): Collection
    {
        $toDeactivate = collect();

        if ($module->on_deactivate === DeactivationBehavior::CASCADE) {
            $dependents = $this->getActiveDependents($module);
            foreach ($dependents as $dependent) {
                if (! ($dependent->is_system ?? false)) {
                    $toDeactivate->push($dependent);
                    $toDeactivate = $toDeactivate->merge(
                        $this->getCascadeDeactivations($dependent)
                    );
                }
            }
        }

        return $toDeactivate->unique('id');
    }

    public function validateNoCircularDependency(Module $module, Module $dependency, array $visited = []): void
    {
        $visited[] = $module->key;

        if ($dependency->key === $module->key || in_array($dependency->key, $visited)) {
            $visited[] = $dependency->key;
            throw new CircularDependencyException($visited);
        }

        foreach ($dependency->getRequirements() as $subDep) {
            $this->validateNoCircularDependency($module, $subDep, $visited);
        }
    }

    public function getMissingDependencies(Module $module): Collection
    {
        return $module->getRequirements()->filter(fn (Module $dep) => ! $dep->is_active);
    }

    public function getActiveConflicts(Module $module): Collection
    {
        return $module->getConflicts()->filter(fn (Module $conflict) => $conflict->is_active);
    }

    public function getActiveDependents(Module $module): Collection
    {
        return $module->getRequiredBy()->filter(fn (Module $dependent) => $dependent->is_active);
    }

    public function getAllDependencies(Module $module, ?Collection $collected = null): Collection
    {
        $collected = $collected ?? collect();

        foreach ($module->getRequirements() as $dep) {
            if (! $collected->contains('id', $dep->id)) {
                $collected->push($dep);
                $collected = $this->getAllDependencies($dep, $collected);
            }
        }

        return $collected;
    }
}
