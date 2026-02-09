<?php

namespace Karnoweb\LaravelModuleManager\Enums;

enum DeactivationBehavior: string
{
    case CASCADE = 'cascade';
    case RESTRICT = 'restrict';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::CASCADE => 'Cascade',
            self::RESTRICT => 'Restrict',
            self::NONE => 'None',
        };
    }
}
