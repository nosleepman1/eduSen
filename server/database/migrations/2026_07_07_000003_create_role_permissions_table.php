<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('role', ['SUPER_ADMIN', 'OWNER', 'ADMIN', 'TEACHER', 'SUPERVISOR', 'ACCOUNTANT', 'SECRETARY', 'LIBRARIAN']);
            $table->json('permissions');
            $table->timestamps();

            $table->unique(['school_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
