<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('level_id')->constrained('levels')->cascadeOnDelete();
            $table->foreignUuid('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('capacity');
            $table->foreignUuid('main_teacher_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('main_room')->nullable();
            $table->enum('status', ['DRAFT', 'ACTIVE', 'CLOSED', 'ARCHIVED'])->default('DRAFT');
            $table->text('description')->nullable();
            $table->date('end_date_override')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('academic_year_id');
            $table->index('level_id');
            $table->index('specialization_id');
            $table->index('main_teacher_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
