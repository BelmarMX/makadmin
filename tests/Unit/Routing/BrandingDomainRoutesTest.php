<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('registers the admin dashboard route on the configured superadmin domain', function (): void {
    $route = Route::getRoutes()->getByName('admin.dashboard');

    expect($route)->not->toBeNull()
        ->and($route->domain())->toBe(config('branding.superadmin_subdomain').'.'.config('branding.apex_domain'));
});

it('registers the clinic dashboard route on the clinic subdomain pattern', function (): void {
    $route = Route::getRoutes()->getByName('clinic.dashboard');

    expect($route)->not->toBeNull()
        ->and($route->domain())->toBe('{clinic}.'.config('branding.apex_domain'));
});

it('stores branding url config values as strings', function (): void {
    expect(config('branding.urls.superadmin_base'))->toBeString()
        ->and(config('branding.urls.superadmin_base'))->toBe(config('branding.superadmin_subdomain').'.'.config('branding.apex_domain'))
        ->and(config('branding.urls.clinic_base'))->toBeString()
        ->and(config('branding.urls.clinic_base'))->toBe('{clinic}.'.config('branding.apex_domain'))
        ->and(config('branding.urls.public_base'))->toBeString()
        ->and(config('branding.urls.public_base'))->toBe(config('branding.public_subdomain').'.'.config('branding.apex_domain'))
        ->and(config('branding.urls.portal_base'))->toBeString()
        ->and(config('branding.urls.portal_base'))->toBe(config('branding.portal_subdomain').'.'.config('branding.apex_domain'));
});
