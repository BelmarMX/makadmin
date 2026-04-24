<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleActive
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $clinic = current_clinic();

        if (! $clinic) {
            abort(403);
        }

        $active = $clinic->modules()
            ->where('module_key', $moduleKey)
            ->where('is_active', true)
            ->exists();

        if (! $active) {
            abort(403, "Module [{$moduleKey}] is not active for this clinic.");
        }

        return $next($request);
    }
}
