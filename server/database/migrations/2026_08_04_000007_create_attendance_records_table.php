<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->enum('status', ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED', 'DISPENSED'])->default('ABSENT');
            $table->time('actual_arrival_time')->nullable();
            $table->string('absence_reason')->nullable();
            $table->text('justification')->nullable();
            $table->enum('justification_status', ['PENDING', 'APPROVED', 'REJECTED'])->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('lesson_session_id');
            $table->index('enrollment_id');
            $table->index('status');
        });

        // Same rationale as lesson_sessions' partial unique index: a plain
        // unique() would collide with a soft-deleted record occupying the
        // same (lesson_session_id, enrollment_id) slot.
        DB::statement('CREATE UNIQUE INDEX attendance_records_session_enrollment_unique ON attendance_records (lesson_session_id, enrollment_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
