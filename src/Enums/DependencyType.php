<?php

namespace Karnoweb\LaravelModuleManager\Enums;

enum DependencyType: string
{
    case REQUIRES = 'requires';
    case CONFLICTS = 'conflicts';
    case SUGGESTS = 'suggests';

    public function label(): string
    {
        return match ($this) {
            self::REQUIRES => 'Required',
            self::CONFLICTS => 'Conflicts',
            self::SUGGESTS => 'Suggests',
        };
    }
}
