<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('email');
            $table->string('firstname');
            $table->string('lastname');
            $table->enum('role', ['OWNER', 'ADMIN', 'TEACHER', 'SUPERVISOR', 'ACCOUNTANT', 'SECRETARY', 'LIBRARIAN']);
            $table->string('token', 64)->unique();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'EXPIRED', 'CANCELLED'])->default('PENDING');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->index('token');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
