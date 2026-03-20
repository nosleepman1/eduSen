<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('schedule_entry_id')->constrained('schedule_entries')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->enum('status', [
                'SCHEDULED', 'PENDING_START', 'IN_PROGRESS', 'PENDING_VALIDATION',
                'COMPLETED', 'CANCELLED', 'POSTPONED', 'ARCHIVED',
            ])->default('SCHEDULED');
            $table->string('cancellation_reason')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('session_date');
            $table->index('status');
        });

        // A plain unique() would collide with a soft-deleted row occupying
        // the same (schedule_entry_id, session_date) slot — regenerate()
        // soft-deletes future sessions before recreating them for the same
        // dates, so the constraint must ignore trashed rows. Both pgsql and
        // sqlite (3.8+) support partial indexes with this exact syntax.
        DB::statement('CREATE UNIQUE INDEX lesson_sessions_entry_date_unique ON lesson_sessions (schedule_entry_id, session_date) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_sessions'); // drops the partial index along with the table
    }
};
