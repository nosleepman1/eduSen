<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->enum('gender', ['MALE', 'FEMALE'])->nullable();
            $table->string('nationality')->nullable()->default('Sénégalaise');
            $table->string('photo')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('medical_info')->nullable();
            $table->string('address')->nullable();
            $table->enum('status', ['ACTIVE', 'TRANSFERRED', 'GRADUATED', 'EXPELLED'])->default('ACTIVE');
            $table->date('enrolled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
