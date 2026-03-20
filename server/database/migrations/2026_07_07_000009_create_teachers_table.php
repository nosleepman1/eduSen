<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->string('speciality')->nullable();
            $table->string('diploma')->nullable();
            $table->string('license_number')->nullable();
            $table->integer('years_experience')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
