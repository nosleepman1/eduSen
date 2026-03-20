<?php

use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The user_roles.role constraint was created with a stale set of values
 * (SUPER_ADMIN, OWNER, ADMIN, TEACHER, PARENT, STUDENT) that no longer matches
 * App\Enums\RoleEnum (SUPER_ADMIN, OWNER, ADMIN, TEACHER, SUPERVISOR, ACCOUNTANT,
 * SECRETARY, LIBRARIAN). Any attempt to assign SUPERVISOR/ACCOUNTANT/SECRETARY/
 * LIBRARIAN fails with a constraint violation. This migration realigns the
 * constraint with RoleEnum (the source of truth, so this stays correct if
 * RoleEnum ever gains/removes a case).
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = array_column(RoleEnum::cases(), 'value');

        if (DB::getDriverName() === 'sqlite') {
            // SQLite embeds CHECK constraints in the table definition itself,
            // so the table must be rebuilt to change the allowed values.
            Schema::create('user_roles_new', function (Blueprint $table) use ($roles) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
                $table->enum('role', $roles);
                $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'school_id', 'role']);
            });

            DB::statement('INSERT INTO user_roles_new SELECT * FROM user_roles');

            Schema::drop('user_roles');
            Schema::rename('user_roles_new', 'user_roles');

            return;
        }

        $rolesList = "'" . implode("','", $roles) . "'";
        DB::statement('ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_role_check');
        DB::statement("ALTER TABLE user_roles ADD CONSTRAINT user_roles_role_check CHECK (role IN ({$rolesList}))");
    }

    public function down(): void
    {
        // Irreversible: the original constraint predates roles now in use.
    }
};
