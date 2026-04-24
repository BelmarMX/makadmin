<?php

namespace App\Providers;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Events\ClinicActivated;
use App\Domain\Clinic\Events\ClinicCreated;
use App\Domain\Clinic\Events\ClinicDeactivated;
use App\Domain\Clinic\Events\ClinicModuleActivated;
use App\Domain\Clinic\Listeners\LogClinicStatusChange;
use App\Domain\Clinic\Listeners\SeedModulePermissionsForClinic;
use App\Domain\Clinic\Listeners\SendClinicWelcomeNotification;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Policies\ClinicBranchPolicy;
use App\Domain\Clinic\Policies\ClinicPolicy;
use App\Integrations\Storage\Local\LocalMediaStorage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaStorage::class, LocalMediaStorage::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerEvents();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : null,
        );
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Clinic::class, ClinicPolicy::class);
        Gate::policy(ClinicBranch::class, ClinicBranchPolicy::class);
    }

    protected function registerEvents(): void
    {
        Event::listen(ClinicCreated::class, SendClinicWelcomeNotification::class);
        Event::listen(ClinicActivated::class, LogClinicStatusChange::class);
        Event::listen(ClinicDeactivated::class, LogClinicStatusChange::class);
        Event::listen(ClinicModuleActivated::class, SeedModulePermissionsForClinic::class);
    }
}
