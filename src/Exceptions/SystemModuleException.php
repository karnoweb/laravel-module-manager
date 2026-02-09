<?php

namespace Karnoweb\LaravelModuleManager\Exceptions;

use Exception;

class SystemModuleException extends Exception
{
    public function __construct(string $key)
    {
        parent::__construct("Module '{$key}' is a system module and cannot be deactivated or deleted.");
    }
}
