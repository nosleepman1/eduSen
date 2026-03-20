<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('phoneable_id');
            $table->string('phoneable_type');
            $table->string('phone_number');
            $table->enum('type', ['MOBILE', 'LANDLINE', 'WHATSAPP', 'EMERGENCY']);
            $table->string('country_code')->default('SN');
            $table->string('dial_code')->default('+221');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['phoneable_id', 'phoneable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
