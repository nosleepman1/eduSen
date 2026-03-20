<?php

namespace App\Listeners;

use App\Events\SchoolRegistrationApproved;
use Database\Seeders\DefaultPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AssignDefaultPermissions implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SchoolRegistrationApproved $event): void
    {
        // When a school is approved and created, seed the default role permissions
        DefaultPermissionsSeeder::seedPermissionsForSchool($event->school->id);
    }
}
