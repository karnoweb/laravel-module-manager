<?php

namespace Karnoweb\LaravelModuleManager\Exceptions;

use Exception;

class DependencyException extends Exception
{
    protected array $dependencies;

    public function __construct(string $message, array $dependencies = [])
    {
        parent::__construct($message);
        $this->dependencies = $dependencies;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
