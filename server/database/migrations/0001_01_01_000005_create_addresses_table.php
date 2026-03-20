<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('addressable_id');
            $table->string('addressable_type');
            $table->string('country')->default('Senegal');
            $table->string('region');
            $table->string('department')->nullable();
            $table->string('city');
            $table->string('commune')->nullable();
            $table->string('street')->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['addressable_id', 'addressable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
