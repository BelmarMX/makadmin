<?php

namespace App\Providers;

use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\PetSize;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Domain\Catalog\Veterinary\Policies\VeterinaryCatalogPolicy;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\Clinic\Policies\ClinicBranchPolicy;
use App\Domain\Clinic\Policies\ClinicPolicy;
use App\Domain\User\Policies\UserPolicy;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Clinic::class, ClinicPolicy::class);
        Gate::policy(ClinicBranch::class, ClinicBranchPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Species::class, VeterinaryCatalogPolicy::class);
        Gate::policy(Breed::class, VeterinaryCatalogPolicy::class);
        Gate::policy(PelageColor::class, VeterinaryCatalogPolicy::class);
        Gate::policy(PetSize::class, VeterinaryCatalogPolicy::class);
        Gate::policy(Temperament::class, VeterinaryCatalogPolicy::class);
    }
}
