<?php

namespace Karnoweb\LaravelModuleManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Karnoweb\LaravelModuleManager\Models\Module;

class ModuleDeactivated
{
    use Dispatchable;

    public function __construct(
        public Module $module
    ) {}
}
