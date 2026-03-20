<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            // Add is_active column
            $table->boolean('is_active')->default(true)->after('assigned_at');
        });

        // Drop the permissions JSON column — permissions now live in role_permissions
        if (Schema::hasColumn('user_roles', 'permissions')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->json('permissions')->nullable()->after('role');
        });
    }
};
