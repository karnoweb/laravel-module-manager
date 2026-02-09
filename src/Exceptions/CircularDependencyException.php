<?php

namespace Karnoweb\LaravelModuleManager\Exceptions;

use Exception;

class CircularDependencyException extends Exception
{
    protected array $chain;

    public function __construct(array $chain)
    {
        $chainStr = implode(' → ', $chain);
        parent::__construct("Circular dependency detected: {$chainStr}");
        $this->chain = $chain;
    }

    public function getChain(): array
    {
        return $this->chain;
    }
}
