<?php

namespace Karnoweb\LaravelModuleManager\Services;

use Illuminate\Support\Collection;
use Karnoweb\LaravelModuleManager\Models\Module;

class TreeBuilder
{
    public function buildTree(?string $group = null): Collection
    {
        $query = Module::with(['children' => function ($q) {
            $q->ordered();
        }])->roots()->ordered();

        if ($group) {
            $query->inGroup($group);
        }

        return $query->get()->map(fn (Module $module) => $this->buildNode($module));
    }

    protected function buildNode(Module $module): array
    {
        return [
            'id' => $module->id,
            'key' => $module->key,
            'name' => $module->name,
            'description' => $module->description,
            'icon' => $module->icon,
            'group' => $module->group,
            'is_active' => $module->is_active,
            'is_system' => (bool) ($module->is_system ?? false),
            'depth' => $module->getDepth(),
            'path' => $module->getPath(),
            'metadata' => $module->metadata,
            'children' => $module->children->map(fn (Module $child) => $this->buildNode($child))->toArray(),
        ];
    }

    public function flatten(?string $group = null): Collection
    {
        $tree = $this->buildTree($group);
        return $this->flattenNodes($tree);
    }

    protected function flattenNodes(Collection|array $nodes, int $depth = 0): Collection
    {
        $result = collect();

        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);
            $node['depth'] = $depth;
            $result->push($node);
            if (! empty($children)) {
                $result = $result->merge($this->flattenNodes($children, $depth + 1));
            }
        }

        return $result;
    }

    public function getGroups(): Collection
    {
        return Module::distinct()
            ->pluck('group')
            ->filter()
            ->sort()
            ->values();
    }
}
