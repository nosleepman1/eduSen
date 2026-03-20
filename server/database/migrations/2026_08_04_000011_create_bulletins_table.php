<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('general_average', 5, 2)->nullable();
            $table->unsignedInteger('class_rank')->nullable();
            $table->unsignedInteger('total_students')->nullable();
            $table->string('mention')->nullable();
            $table->text('class_council_comment')->nullable();
            $table->enum('status', ['DRAFT', 'VALIDATED', 'PUBLISHED', 'LOCKED', 'ARCHIVED'])->default('DRAFT');
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('academic_period_id');
            $table->index('enrollment_id');
            $table->index('status');
        });

        // Same rationale as every other partial unique index in this domain.
        DB::statement('CREATE UNIQUE INDEX bulletins_period_enrollment_unique ON bulletins (academic_period_id, enrollment_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};
