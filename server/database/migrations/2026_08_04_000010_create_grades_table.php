<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('value', 5, 2)->nullable();
            $table->enum('status', ['PENDING', 'GRADED', 'ABSENT', 'DISPENSED'])->default('PENDING');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('evaluation_id');
            $table->index('enrollment_id');
            $table->index('status');
        });

        // Same rationale as every other *_unique partial index in this
        // domain: a plain unique() would collide with a soft-deleted row
        // occupying the same (evaluation_id, enrollment_id) slot.
        DB::statement('CREATE UNIQUE INDEX grades_evaluation_enrollment_unique ON grades (evaluation_id, enrollment_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
