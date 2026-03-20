<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phone');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('profession')->nullable();
            $table->string('address')->nullable();
            $table->string('national_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
