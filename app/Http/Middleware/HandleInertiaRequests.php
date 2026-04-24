<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'branding' => [
                'apexDomain' => config('branding.apex_domain'),
                'publicSubdomain' => config('branding.public_subdomain'),
                'superadminSubdomain' => config('branding.superadmin_subdomain'),
                'portalSubdomain' => config('branding.portal_subdomain'),
                'scheme' => config('branding.scheme'),
            ],
            'auth' => [
                'user' => $request->user(),
            ],
            'context' => $this->resolveContext($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function resolveContext(Request $request): string
    {
        $host = $request->getHost();
        $superadmin = config('branding.superadmin_subdomain').'.'.config('branding.apex_domain');
        $apex = config('branding.apex_domain');

        if ($host === $superadmin) {
            return 'admin';
        }

        if ($host !== $apex && str_ends_with($host, '.'.$apex)) {
            return 'clinic';
        }

        return 'app';
    }
}
