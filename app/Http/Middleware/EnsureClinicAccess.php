<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        $clinic = app('current.clinic');

        if ($user->clinic_id !== $clinic->id) {
            abort(403, 'No perteneces a esta clínica.');
        }

        return $next($request);
    }
}
