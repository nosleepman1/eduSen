<?php

namespace App\Support;

use App\Models\School;

class TenantHelper
{
    /**
     * Get the current tenant (school) ID from the application container.
     */
    public static function currentSchoolId(): ?string
    {
        if (app()->bound('current_school_id')) {
            return app('current_school_id');
        }

        return null;
    }

    /**
     * Get the current tenant (school) model instance.
     */
    public static function currentSchool(): ?School
    {
        $id = self::currentSchoolId();

        return $id ? School::find($id) : null;
    }

    /**
     * Set the current tenant context.
     */
    public static function setCurrentSchool(string $schoolId): void
    {
        app()->instance('current_school_id', $schoolId);
    }

    /**
     * Clear the current tenant context.
     */
    public static function clearCurrentSchool(): void
    {
        app()->forgetInstance('current_school_id');
    }

    /**
     * Abort with 403 unless the given school_id belongs to the current tenant.
     * Use for route-model-bound records to prevent cross-tenant access.
     */
    public static function assertOwnedByCurrentSchool(?string $schoolId): void
    {
        if ($schoolId === null || $schoolId !== self::currentSchoolId()) {
            // The exception's message is what bootstrap/app.php's 403 handler
            // surfaces to the client — abort(403) with no message left every
            // cross-tenant refusal in the API as message: "".
            abort(403, "Cette ressource n'appartient pas à votre établissement.");
        }
    }
}
