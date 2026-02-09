<?php

namespace Karnoweb\LaravelModuleManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Karnoweb\LaravelModuleManager\Facades\Module;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleIsActive
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        foreach ($modules as $module) {
            if (Module::inactive($module)) {
                abort(403, "Module '{$module}' is not active.");
            }
        }

        return $next($request);
    }
}
