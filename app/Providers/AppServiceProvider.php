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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        // Sub-path deployment fix (staging lives at /ankole-profiler).
        //
        // Livewire builds its frontend update URI from route('livewire.update',
        // [], false) — a relative path equal to the route's own URI. By default
        // that is "/livewire/update", with no sub-path, so the browser POSTs to
        // the domain root and 404s, breaking every Livewire form submit.
        //
        // nginx serves this app under /ankole-profiler but passes the request to
        // PHP with SCRIPT_NAME=/ankole-profiler/index.php, so Laravel strips the
        // prefix and matches routes WITHOUT it. That means the two needs differ:
        //   - the browser-facing URI must INCLUDE the prefix, and
        //   - the matched route must be the stripped "/livewire/update".
        // One route can't be both, so we register the named route at the
        // prefixed path (drives the correct frontend URI) and an unnamed route
        // at the stripped path (catches the actual, prefix-stripped request).
        $basePath = rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?? '', '/');
        if ($basePath !== '') {
            $handler = [\Livewire\Mechanisms\HandleRequests\HandleRequests::class, 'handleUpdate'];
            Livewire::setUpdateRoute(fn ($handle) => Route::post($basePath . '/livewire/update', $handle));
            Route::post('/livewire/update', $handler)->middleware('web');
        }

        Gate::policy(OrganizationUnit::class, OrganizationUnitPolicy::class);
        Gate::policy(OrganizationUnitApplication::class, OrganizationUnitApplicationPolicy::class);
        Gate::policy(RoleType::class, RoleTypePolicy::class);
        Gate::policy(PersonAffiliation::class, PersonAffiliationPolicy::class);

        Gate::before(function ($user) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
