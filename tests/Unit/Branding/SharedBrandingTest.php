<?php

use App\Domain\Clinic\Models\Clinic;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveClinic;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class);

it('shares branding config with inertia page props', function (): void {
    config([
        'branding.apex_domain' => 'makadmin.test',
        'branding.public_subdomain' => 'site',
        'branding.superadmin_subdomain' => 'radar',
        'branding.portal_subdomain' => 'portal',
        'branding.scheme' => 'https',
    ]);

    $shared = app(HandleInertiaRequests::class)->share(Request::create('/'));

    expect($shared['branding'])->toBe([
        'apexDomain' => 'makadmin.test',
        'publicSubdomain' => 'site',
        'superadminSubdomain' => 'radar',
        'portalSubdomain' => 'portal',
        'scheme' => 'https',
    ]);
});

it('builds clinic subdomain urls from branding config', function (): void {
    config([
        'branding.apex_domain' => 'makadmin.test',
        'branding.scheme' => 'https',
    ]);

    $clinic = new Clinic(['slug' => 'north']);

    expect($clinic->subdomain_url)->toBe('https://north.makadmin.test');
});

it('allows the configured superadmin subdomain without resolving a clinic', function (): void {
    config([
        'branding.apex_domain' => 'makadmin.test',
        'branding.superadmin_subdomain' => 'radar',
        'branding.public_subdomain' => 'site',
    ]);

    $response = app(ResolveClinic::class)->handle(Request::create('https://radar.makadmin.test/'), fn () => response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and(app()->bound('current.clinic'))->toBeFalse();
});

it('blocks the configured public subdomain before clinic lookup', function (): void {
    config([
        'branding.apex_domain' => 'makadmin.test',
        'branding.public_subdomain' => 'site',
        'branding.superadmin_subdomain' => 'radar',
    ]);

    expect(fn () => app(ResolveClinic::class)->handle(Request::create('https://site.makadmin.test/'), fn () => response('ok')))
        ->toThrow(HttpException::class);
});
