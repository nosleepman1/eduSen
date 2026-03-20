<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('bulletin_id')->constrained('bulletins')->cascadeOnDelete();
            $table->foreignUuid('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->decimal('subject_average', 5, 2)->nullable();
            $table->decimal('coefficient', 5, 2);
            $table->decimal('points', 6, 2)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('bulletin_id');
            $table->index('curriculum_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_lines');
    }
};
