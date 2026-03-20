<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultPermissionsSeeder extends Seeder
{
    /**
     * Default permissions template for each role.
     * When a school is created, these get copied into role_permissions.
     */
    public static function defaultPermissions(): array
    {
        return [
            // Owner has ALL permissions — no need to list them (bypassed in middleware)

            RoleEnum::Admin->value => [
                'personnel.view', 'personnel.create', 'personnel.update', 'personnel.delete',
                'student.view', 'student.create', 'student.update', 'student.delete',
                'parent.view', 'parent.create', 'parent.update', 'parent.delete',
                'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete',
                'class.view', 'class.create', 'class.update', 'class.delete',
                'payment.view', 'payment.create',
                'report.view',
                'invitation.create', 'invitation.view',
                'settings.view', 'settings.update',
                'academic-year.view', 'academic-year.manage',
                'academic-period.view', 'academic-period.manage',
                'school-calendar.view', 'school-calendar.manage',
                'specialization.view', 'specialization.manage',
                'level.view', 'level.manage',
                'classroom.view', 'classroom.manage',
                'subject.view', 'subject.manage',
                'curriculum.view', 'curriculum.manage',
                'enrollment.view', 'enrollment.manage',
                'teaching-assignment.view', 'teaching-assignment.manage',
                'timetable.view', 'timetable.manage',
                'schedule-entry.view', 'schedule-entry.manage',
                'lesson-session.view', 'lesson-session.manage',
                'attendance-record.view', 'attendance-record.manage',
                'lesson-content.view', 'lesson-content.manage',
                'evaluation.view', 'evaluation.manage',
                'grade.view', 'grade.manage',
                'bulletin.view', 'bulletin.manage',
            ],

            RoleEnum::Teacher->value => [
                'student.view',
                'parent.view',
                'class.view',
                'attendance.view', 'attendance.create',
                'report.view',
                'academic-year.view', 'academic-period.view', 'school-calendar.view',
                'level.view', 'classroom.view', 'subject.view', 'curriculum.view',
                'enrollment.view',
                'teaching-assignment.view',
                'timetable.view',
                'schedule-entry.view',
                'lesson-session.view', 'lesson-session.manage',
                'attendance-record.view', 'attendance-record.manage',
                'lesson-content.view', 'lesson-content.manage',
                'evaluation.view', 'evaluation.manage',
                'grade.view', 'grade.manage',
                'bulletin.view',
            ],

            RoleEnum::Supervisor->value => [
                'student.view', 'student.update',
                'parent.view',
                'teacher.view',
                'class.view',
                'attendance.view', 'attendance.create',
                'discipline.view', 'discipline.create',
                'report.view',
                'academic-year.view', 'academic-period.view', 'school-calendar.view',
                'level.view', 'classroom.view', 'subject.view', 'curriculum.view',
                'enrollment.view',
                'teaching-assignment.view',
                'timetable.view',
                'schedule-entry.view',
                'lesson-session.view', 'lesson-session.manage',
                'attendance-record.view', 'attendance-record.manage',
                'lesson-content.view',
                'evaluation.view',
                'grade.view',
                'bulletin.view', 'bulletin.manage',
            ],

            RoleEnum::Accountant->value => [
                'student.view',
                'payment.view', 'payment.create', 'payment.refund',
                'invoice.view', 'invoice.create',
                'report.view', 'report.financial',
                'academic-year.view',
                'enrollment.view',
            ],

            RoleEnum::Secretary->value => [
                'student.view', 'student.create', 'student.update',
                'parent.view', 'parent.create', 'parent.update',
                'class.view',
                'document.view', 'document.create',
                'report.view',
                'academic-year.view', 'academic-period.view', 'school-calendar.view',
                'level.view', 'classroom.view', 'subject.view', 'curriculum.view',
                'enrollment.view', 'enrollment.manage',
            ],

            RoleEnum::Librarian->value => [
                'student.view',
                'library.view', 'library.create', 'library.update', 'library.delete',
                'report.view',
            ],
        ];
    }

    public function run(): void
    {
        // This seeder can be used to populate role_permissions for a specific school.
        // It's designed to be called programmatically, not just via artisan.
        $this->command?->info('DefaultPermissionsSeeder is designed to be called via seedPermissionsForSchool($schoolId).');
    }

    /**
     * Seed default permissions for a newly created school.
     */
    public static function seedPermissionsForSchool(string $schoolId): void
    {
        $defaults = self::defaultPermissions();

        foreach ($defaults as $role => $permissions) {
            DB::table('role_permissions')->updateOrInsert(
                ['school_id' => $schoolId, 'role' => $role],
                [
                    'id' => (string) Str::uuid(),
                    'permissions' => json_encode($permissions),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // These writes go through the query builder, so RolePermissionObserver
        // never fires. Re-seeding an existing school would otherwise keep
        // serving the previous permission set until the cache expired.
        RolePermission::clearCache($schoolId);
    }
}
