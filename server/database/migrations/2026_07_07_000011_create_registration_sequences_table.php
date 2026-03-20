<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['STUDENT', 'EMPLOYEE', 'PAYMENT', 'INVOICE']);
            $table->integer('year');
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_sequences');
    }
};
