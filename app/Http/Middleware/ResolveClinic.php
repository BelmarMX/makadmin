<?php

namespace App\Http\Middleware;

use App\Domain\Clinic\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveClinic
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $apex = config('branding.apex_domain');
        $publicSubdomain = config('branding.public_subdomain');
        $superadminSubdomain = config('branding.superadmin_subdomain');

        $subdomain = str($host)->before('.'.$apex)->value();

        if (! $subdomain || $subdomain === $host || $subdomain === $publicSubdomain) {
            abort(404);
        }

        if ($subdomain === $superadminSubdomain) {
            return $next($request);
        }

        $clinic = Clinic::where('slug', $subdomain)
            ->where('is_active', true)
            ->first();

        if (! $clinic) {
            abort(404);
        }

        app()->instance('current.clinic', $clinic);
        config(['permission.team_id' => $clinic->id]);
        setPermissionsTeamId($clinic->id);

        return $next($request);
    }
}
