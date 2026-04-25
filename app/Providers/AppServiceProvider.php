<?php

namespace App\Providers;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Catalog\Geographic\Commands\SyncSepomexCommand;
use App\Domain\Clinic\Events\ClinicActivated;
use App\Domain\Clinic\Events\ClinicCreated;
use App\Domain\Clinic\Events\ClinicDeactivated;
use App\Domain\Clinic\Events\ClinicModuleActivated;
use App\Domain\Clinic\Listeners\LogClinicStatusChange;
use App\Domain\Clinic\Listeners\SeedModulePermissionsForClinic;
use App\Domain\Clinic\Listeners\SendClinicWelcomeNotification;
use App\Integrations\Storage\Local\LocalMediaStorage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaStorage::class, LocalMediaStorage::class);
        $this->commands([SyncSepomexCommand::class]);
    }

    public function boot(): void
    {
        $this->configureDefaults();
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

    protected function registerEvents(): void
    {
        Event::listen(ClinicCreated::class, SendClinicWelcomeNotification::class);
        Event::listen(ClinicActivated::class, LogClinicStatusChange::class);
        Event::listen(ClinicDeactivated::class, LogClinicStatusChange::class);
        Event::listen(ClinicModuleActivated::class, SeedModulePermissionsForClinic::class);
    }
}
