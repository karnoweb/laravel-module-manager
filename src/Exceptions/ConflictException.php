<?php

namespace Karnoweb\LaravelModuleManager\Exceptions;

use Exception;

class ConflictException extends Exception
{
    protected array $conflicts;

    public function __construct(string $module, array $conflicts)
    {
        $conflictList = implode(', ', $conflicts);
        parent::__construct("Module '{$module}' conflicts with: {$conflictList}");
        $this->conflicts = $conflicts;
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
