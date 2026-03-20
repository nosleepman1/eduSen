<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#1E6CA6');
            $table->string('secondary_color')->default('#FFFFFF');
            $table->string('slogan')->nullable();
            $table->string('website')->nullable();
            $table->enum('school_type', ['PRIMARY', 'MIDDLE', 'HIGH', 'MIXED'])->default('MIXED');
            $table->enum('sector', ['PRIVATE_SECULAR', 'PRIVATE_CATHOLIC', 'PRIVATE_ISLAMIC', 'FRANCO_ARABIC'])->default('PRIVATE_SECULAR');
            $table->string('registration_number')->nullable();
            $table->date('founded_at')->nullable();
            $table->integer('max_students')->nullable();
            $table->integer('max_teachers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_details');
    }
};
