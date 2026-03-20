<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedInteger('display_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'name']);
            $table->unique(['academic_year_id', 'code']);
            $table->index('school_id');
            $table->index('specialization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
