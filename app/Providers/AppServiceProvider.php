<?php

namespace App\Providers;

use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;
use App\Models\PersonAffiliation;
use App\Models\RoleType;
use App\Policies\OrganizationUnitApplicationPolicy;
use App\Policies\OrganizationUnitPolicy;
use App\Policies\PersonAffiliationPolicy;
use App\Policies\RoleTypePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(OrganizationUnit::class, OrganizationUnitPolicy::class);
        Gate::policy(OrganizationUnitApplication::class, OrganizationUnitApplicationPolicy::class);
        Gate::policy(RoleType::class, RoleTypePolicy::class);
        Gate::policy(PersonAffiliation::class, PersonAffiliationPolicy::class);

        Gate::before(function ($user) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
