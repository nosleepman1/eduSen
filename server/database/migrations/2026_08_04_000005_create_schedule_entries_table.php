<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignUuid('teaching_assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();
            $table->enum('day_of_week', ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->enum('frequency', ['WEEKLY'])->default('WEEKLY');
            $table->enum('status', ['ACTIVE', 'ARCHIVED'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('timetable_id');
            $table->index('teaching_assignment_id');
            $table->index('day_of_week');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};
