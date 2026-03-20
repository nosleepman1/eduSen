<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first if it exists
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['school_id']);
            } else {
                // SQLite specific: Drop the index before dropping the column
                $table->dropIndex('users_school_id_index');
            }

            $table->dropColumn([
                'school_id',
                'role',
                'active',
                'type_auth',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->index()->after('id');
            $table->enum('role', ['SUPER_ADMIN', 'OWNER', 'ADMIN', 'TEACHER', 'PARENT', 'STUDENT'])->after('gender');
            $table->boolean('active')->default(true)->after('status');
            $table->enum('type_auth', ['EMAIL_PASSWORD', 'OTP_PHONE'])->default('EMAIL_PASSWORD')->after('active');
        });
    }
};
