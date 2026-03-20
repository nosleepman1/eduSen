<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignUuid('teaching_assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();
            $table->foreignUuid('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();
            $table->enum('type', ['DEVOIR', 'EXAMEN']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('evaluation_date');
            $table->decimal('max_score', 5, 2);
            $table->decimal('coefficient', 5, 2)->nullable();
            $table->enum('status', ['DRAFT', 'OPEN', 'CLOSED', 'LOCKED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('academic_year_id');
            $table->index('academic_period_id');
            $table->index('teaching_assignment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
