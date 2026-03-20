<?php

namespace App\Providers;

use App\Listeners\NotifyDirectorOfAcademicChange;
use App\Models\RolePermission;
use App\Observers\RolePermissionObserver;
use Illuminate\Support\Facades\Event;
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
        RolePermission::observe(RolePermissionObserver::class);

        Event::subscribe(NotifyDirectorOfAcademicChange::class);
    }
}
