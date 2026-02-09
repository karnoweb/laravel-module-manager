<?php

namespace Karnoweb\LaravelModuleManager\Exceptions;

use Exception;

class ModuleNotFoundException extends Exception
{
    public function __construct(string $key)
    {
        parent::__construct("Module '{$key}' not found.");
    }
}
